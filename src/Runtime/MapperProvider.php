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
     * @var array<string, Mapper<mixed>>
     */
    private array $mappers = [];

    /**
     * @var array<class-string, callable(never, list<Mapper<mixed>>, self): Mapper<mixed>>
     */
    private array $mapperFactories = [];

    public function __construct(
        private readonly string $tempDir,
        private readonly bool $autoRefresh = false,
        private readonly MapperCompilerFactoryProvider $mapperCompilerFactoryProvider = new DefaultMapperCompilerFactoryProvider(),
    )
    {
    }

    /**
     * @template T of object
     * @param  class-string<T>     $inputClassName
     * @param  list<Mapper<mixed>> $innerMappers
     * @return Mapper<T>
     */
    public function get(string $inputClassName, array $innerMappers = []): Mapper
    {
        $key = $inputClassName;

        if (count($innerMappers) > 0) {
            $key .= '+' . md5(implode('+', array_map(spl_object_id(...), $innerMappers)));
        }

        /** @var Mapper<T> $mapper */
        $mapper = $this->mappers[$key] ??= $this->create($inputClassName, $innerMappers);
        return $mapper;
    }

    /**
     * @template T of object
     * @param  class-string<T>                                                 $inputClassName
     * @param  callable(class-string<T>, list<Mapper<mixed>>, self): Mapper<T> $mapperFactory
     */
    public function registerFactory(string $inputClassName, callable $mapperFactory): void
    {
        if (isset($this->mappers[$inputClassName])) {
            throw new LogicException("Mapper for '$inputClassName' already created.");
        }

        $this->mapperFactories[$inputClassName] = $mapperFactory;
    }

    /**
     * @template T of object
     * @param  class-string<T>     $inputClassName
     * @param  list<Mapper<mixed>> $innerMappers
     * @return Mapper<T>
     */
    private function create(string $inputClassName, array $innerMappers): Mapper
    {
        $classParents = class_parents($inputClassName);
        $classImplements = class_implements($inputClassName);

        if ($classParents === false || $classImplements === false) {
            throw new LogicException("Unable to get class parents or implements for '$inputClassName'.");
        }

        $classLikeNames = [$inputClassName => true, ...$classParents, ...$classImplements];

        foreach ($classLikeNames as $classLikeName => $_) {
            if (isset($this->mapperFactories[$classLikeName])) {
                /** @var callable(class-string<T>, list<Mapper<mixed>>, self): Mapper<T> $factory */
                $factory = $this->mapperFactories[$classLikeName];
                return $factory($inputClassName, $innerMappers, $this);
            }
        }

        $mapperClassName = $this->getMapperClass($inputClassName);

        if (!class_exists($mapperClassName, autoload: false)) {
            $this->load($inputClassName, $mapperClassName);
        }

        return new $mapperClassName($this, $innerMappers);
    }

    /**
     * @template T of object
     * @param class-string<T>         $inputClassName
     * @param class-string<Mapper<T>> $mapperClassName
     */
    private function load(string $inputClassName, string $mapperClassName): void
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
            $code = $this->compile($inputClassName, $mapperClassName);

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
     * @template T of object
     * @param class-string<T>         $inputClassName
     * @param class-string<Mapper<T>> $mapperClassName
     */
    private function compile(string $inputClassName, string $mapperClassName): string
    {
        $mapperCompilerFactory = $this->mapperCompilerFactoryProvider->get();
        $mapperCompiler = $mapperCompilerFactory->create(new IdentifierTypeNode($inputClassName));

        $codeBuilder = new PhpCodeBuilder();
        $codePrinter = new PhpCodePrinter();

        return $codePrinter->prettyPrintFile($codeBuilder->mapperFile($mapperClassName, $mapperCompiler));
    }

    /**
     * @template T of object
     * @param  class-string<T> $inputClassName
     * @return class-string<Mapper<T>>
     */
    private function getMapperClass(string $inputClassName): string
    {
        $shortName = $this->getShortName($inputClassName);
        $hash = substr(md5($inputClassName), 0, 8);

        // @phpstan-ignore-next-line
        return "ShipMonk\\InputMapper\\Runtime\\Generated\\{$shortName}Mapper_{$hash}";
    }

    /**
     * @param class-string<Mapper<mixed>> $mapperClassName
     */
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
