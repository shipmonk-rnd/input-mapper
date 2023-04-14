<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Runtime;

use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TypeParser;
use RuntimeException;
use ShipMonk\InputMapper\Compiler\Generator;
use ShipMonk\InputMapper\Compiler\MapperFactory\MapperCompilerFactory;
use function class_exists;
use function dirname;
use function file_put_contents;
use function flock;
use function fopen;
use function is_dir;
use function is_file;
use function md5;
use function mkdir;
use function rename;
use function strlen;
use function strrpos;
use function substr;
use function unlink;
use const LOCK_EX;
use const LOCK_UN;

class MapperProvider
{

    /**
     * @var array<class-string, Mapper<mixed>>
     */
    private array $registry = [];

    public function __construct(
        private readonly string $tempDir,
        private readonly bool $autoRefresh = false,
    )
    {
    }

    /**
     * @template T of object
     * @param  class-string<T> $className
     * @return Mapper<T>
     */
    public function get(string $className): Mapper
    {
        /** @var Mapper<T> $mapper */
        $mapper = $this->registry[$className] ??= $this->create($className);
        return $mapper;
    }

    /**
     * @template T of object
     * @param  class-string<T> $className
     * @return Mapper<T>
     */
    private function create(string $className): Mapper
    {
        $mapperClassName = $this->getMapperClass($className);

        if (!class_exists($mapperClassName, autoload: false)) {
            $this->load($className, $mapperClassName);
        }

        return new $mapperClassName($this);
    }

    /**
     * @template T of object
     * @param class-string<T>         $className
     * @param class-string<Mapper<T>> $mapperClassName
     */
    private function load(string $className, string $mapperClassName): void
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
            $code = $this->compile($className, $mapperClassName);

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
     * @param class-string<T>         $className
     * @param class-string<Mapper<T>> $mapperClassName
     */
    private function compile(string $className, string $mapperClassName): string
    {
        $phpDocLexer = new Lexer();
        $phpDocExprParser = new ConstExprParser();
        $phpDocTypeParser = new TypeParser($phpDocExprParser);
        $phpDocParser = new PhpDocParser($phpDocTypeParser, $phpDocExprParser);

        $mapperCompilerFactory = new MapperCompilerFactory($phpDocLexer, $phpDocParser);
        $mapperCompiler = $mapperCompilerFactory->createObjectMapper($className);

        $generator = new Generator();
        return $generator->generateMapperFile($mapperClassName, $mapperCompiler);
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
