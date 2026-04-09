<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Runtime;

use LogicException;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use RuntimeException;
use ShipMonk\InputMapper\Compiler\MapperFactory\DefaultMapperCompilerFactoryProvider;
use ShipMonk\InputMapper\Compiler\MapperFactory\MapperCompilerFactoryProvider;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Compiler\Php\PhpCodePrinter;
use function array_map;
use function class_exists;
use function class_implements;
use function class_parents;
use function count;
use function dirname;
use function file_put_contents;
use function flock;
use function fopen;
use function implode;
use function is_dir;
use function is_file;
use function md5;
use function mkdir;
use function rename;
use function spl_object_id;
use function strlen;
use function strrpos;
use function substr;
use function unlink;
use const LOCK_EX;
use const LOCK_UN;

class MapperProvider
{

    /**
     * @var array<string, Mapper<mixed, mixed>>
     */
    private array $inputMappers = [];

    /**
     * @var array<string, Mapper<mixed, mixed>>
     */
    private array $outputMappers = [];

    /**
     * @var array<class-string, callable(class-string, list<Mapper<*, *>>, self): Mapper<mixed, mixed>>
     */
    private array $inputMapperFactories = [];

    /**
     * @var array<class-string, callable(class-string, list<Mapper<*, *>>, self): Mapper<mixed, mixed>>
     */
    private array $outputMapperFactories = [];

    public function __construct(
        private readonly string $tempDir,
        private readonly bool $autoRefresh = false,
        private readonly MapperCompilerFactoryProvider $mapperCompilerFactoryProvider = new DefaultMapperCompilerFactoryProvider(),
    )
    {
    }

    /**
     * @param class-string<T> $className
     * @param list<Mapper<*, *>> $genericInnerMappers
     * @return Mapper<mixed, T>
     *
     * @template T of object
     */
    public function getInputMapper(
        string $className,
        array $genericInnerMappers = [],
    ): Mapper
    {
        $key = $this->getCacheKey($className, $genericInnerMappers);

        /** @var Mapper<mixed, T> $mapper */
        $mapper = $this->inputMappers[$key] ??= $this->createMapper($className, $genericInnerMappers, $this->inputMapperFactories, 'input');
        return $mapper;
    }

    /**
     * @param class-string<T> $className
     * @param list<Mapper<*, *>> $genericInnerMappers
     * @return Mapper<T, mixed>
     *
     * @template T of object
     */
    public function getOutputMapper(
        string $className,
        array $genericInnerMappers = [],
    ): Mapper
    {
        $key = $this->getCacheKey($className, $genericInnerMappers);

        /** @var Mapper<T, mixed> $mapper */
        $mapper = $this->outputMappers[$key] ??= $this->createMapper($className, $genericInnerMappers, $this->outputMapperFactories, 'output');
        return $mapper;
    }

    /**
     * @param class-string<T> $className
     * @param callable(class-string<T>, list<Mapper<mixed, mixed>>, self): Mapper<mixed, T> $mapperFactory
     *
     * @template T of object
     */
    public function registerInputFactory(
        string $className,
        callable $mapperFactory,
    ): void
    {
        if (isset($this->inputMappers[$className])) {
            throw new LogicException("Input mapper for '$className' already created.");
        }

        $this->inputMapperFactories[$className] = $mapperFactory; // @phpstan-ignore assign.propertyType
    }

    /**
     * @param class-string<T> $className
     * @param callable(class-string<T>, list<Mapper<mixed, mixed>>, self): Mapper<T, mixed> $mapperFactory
     *
     * @template T of object
     */
    public function registerOutputFactory(
        string $className,
        callable $mapperFactory,
    ): void
    {
        if (isset($this->outputMappers[$className])) {
            throw new LogicException("Output mapper for '$className' already created.");
        }

        $this->outputMapperFactories[$className] = $mapperFactory; // @phpstan-ignore assign.propertyType
    }

    /**
     * @param list<Mapper<*, *>> $genericInnerMappers
     */
    private function getCacheKey(
        string $className,
        array $genericInnerMappers,
    ): string
    {
        $key = $className;

        if (count($genericInnerMappers) > 0) {
            $key .= '+' . md5(implode('+', array_map(spl_object_id(...), $genericInnerMappers)));
        }

        return $key;
    }

    /**
     * @param class-string<T> $className
     * @param list<Mapper<*, *>> $genericInnerMappers
     * @param array<class-string, callable(class-string, list<Mapper<*, *>>, self): Mapper<mixed, mixed>> $factories
     * @param 'input'|'output' $direction
     * @return Mapper<mixed, mixed>
     *
     * @template T of object
     */
    private function createMapper(
        string $className,
        array $genericInnerMappers,
        array $factories,
        string $direction,
    ): Mapper
    {
        $classParents = class_parents($className);
        $classImplements = class_implements($className);

        if ($classParents === false || $classImplements === false) {
            throw new LogicException("Unable to get class parents or implements for '$className'.");
        }

        $classLikeNames = [$className => true, ...$classParents, ...$classImplements];

        foreach ($classLikeNames as $classLikeName => $true) {
            if (isset($factories[$classLikeName])) {
                $factory = $factories[$classLikeName];
                return $factory($className, $genericInnerMappers, $this);
            }
        }

        $mapperClassName = $this->getMapperClass($className, $direction);

        if (!class_exists($mapperClassName, autoload: false)) {
            $this->load($className, $mapperClassName, $direction);
        }

        return new $mapperClassName($this, $genericInnerMappers);
    }

    /**
     * @param class-string $className
     * @param class-string<Mapper<mixed, mixed>> $mapperClassName
     * @param 'input'|'output' $direction
     */
    private function load(
        string $className,
        string $mapperClassName,
        string $direction,
    ): void
    {
        $path = $this->getMapperPath($mapperClassName);

        if (!$this->autoRefresh && (@include $path) !== false) { // @ file may not exist
            return;
        }

        if (!is_dir(dirname($path))) {
            @mkdir(dirname($path)); // @ directory may already exist
        }

        $handle = fopen("$path.lock", 'c+');

        if ($handle === false) {
            throw new RuntimeException("Unable to write lock '$path.lock'.");
        }

        if (!flock($handle, LOCK_EX)) {
            throw new RuntimeException("Unable to acquire exclusive lock '$path.lock'.");
        }

        if (!is_file($path) || $this->autoRefresh) {
            $code = $this->compile($className, $mapperClassName, $direction);

            if (file_put_contents("$path.tmp", $code) !== strlen($code) || !rename("$path.tmp", $path)) {
                @unlink("$path.tmp"); // @ file may not exist
                throw new RuntimeException("Unable to create '$path'.");
            }
        }

        if ((@include $path) === false) { // @ error escalated to exception
            throw new RuntimeException("Unable to load '$path'.");
        }

        flock($handle, LOCK_UN);
    }

    /**
     * @param class-string $className
     * @param class-string<Mapper<mixed, mixed>> $mapperClassName
     * @param 'input'|'output' $direction
     */
    private function compile(
        string $className,
        string $mapperClassName,
        string $direction,
    ): string
    {
        $mapperCompilerFactory = $this->mapperCompilerFactoryProvider->get();
        $type = new IdentifierTypeNode($className);

        $codeBuilder = new PhpCodeBuilder();
        $codePrinter = new PhpCodePrinter();

        if ($direction === 'input') {
            $mapperCompiler = $mapperCompilerFactory->create($type)->getInputMapperCompiler();
            return $codePrinter->prettyPrintFile($codeBuilder->inputMapperFile($mapperClassName, $mapperCompiler));
        }

        $mapperCompiler = $mapperCompilerFactory->create($type)->getOutputMapperCompiler();
        return $codePrinter->prettyPrintFile($codeBuilder->outputMapperFile($mapperClassName, $mapperCompiler));
    }

    /**
     * @param class-string $className
     * @param 'input'|'output' $direction
     * @return class-string<Mapper<mixed, mixed>>
     */
    private function getMapperClass(
        string $className,
        string $direction,
    ): string
    {
        $shortName = $this->getShortName($className);
        $hash = substr(md5($className), 0, 8);
        $suffix = $direction === 'input' ? 'Mapper' : 'OutputMapper';

        // @phpstan-ignore-next-line
        return "ShipMonk\\InputMapper\\Runtime\\Generated\\{$shortName}{$suffix}_{$hash}";
    }

    private function getMapperPath(string $mapperClassName): string
    {
        $shortName = $this->getShortName($mapperClassName);
        return "{$this->tempDir}/{$shortName}.php";
    }

    private function getShortName(string $className): string
    {
        $pos = strrpos($className, '\\');
        return $pos === false ? $className : substr($className, $pos + 1);
    }

}
