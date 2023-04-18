<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper;

use Nette\Utils\FileSystem;
use PHPUnit\Framework\Constraint\Exception as ExceptionConstraint;
use PHPUnit\Framework\Constraint\ExceptionMessage as ExceptionMessageConstraint;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ShipMonk\InputMapper\Compiler\Generator;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use Throwable;
use function assert;
use function class_exists;
use function getenv;
use function is_file;
use function str_replace;
use function strtr;
use function ucfirst;

abstract class MapperCompilerTestCase extends TestCase
{

    /**
     * @return Mapper<mixed>
     */
    protected function compileMapper(
        string $name,
        MapperCompiler $mapperCompiler,
        ?MapperProvider $mapperProvider = null
    ): Mapper
    {
        $testCaseReflection = new ReflectionClass($this);

        $mapperShortClassName = ucfirst($name) . 'Mapper';
        $mapperNamespace = $testCaseReflection->getNamespaceName() . '\\Data';
        $mapperClassName = "{$mapperNamespace}\\{$mapperShortClassName}";
        $mapperPath = strtr(str_replace(__NAMESPACE__, __DIR__, $mapperNamespace), '\\', '/') . '/' . $mapperShortClassName . '.php';

        if (!class_exists($mapperClassName, autoload: false)) {
            $generator = new Generator();
            $expectedMapperCode = $generator->generateMapperFile($mapperClassName, $mapperCompiler);

            if (is_file($mapperPath) && getenv('REGENERATE_MAPPERS') === false) {
                $actualMapperCode = FileSystem::read($mapperPath);
                self::assertSame($expectedMapperCode, $actualMapperCode);

            } elseif (getenv('CI') === false) {
                FileSystem::write($mapperPath, $expectedMapperCode);

            } else {
                self::fail("Mapper file {$mapperPath} does not exist. Run tests locally to generate it.");
            }

            require $mapperPath;
        }

        $mapperProvider ??= $this->createMock(MapperProvider::class);
        $mapper = new $mapperClassName($mapperProvider);
        assert($mapper instanceof Mapper);

        return $mapper;
    }

    /**
     * @template T of Throwable
     * @param  class-string<T> $type
     * @param  callable(): mixed $cb
     */
    protected static function assertException(string $type, ?string $message, callable $cb): void
    {
        try {
            $cb();
            self::assertThat(null, new ExceptionConstraint($type));
        } catch (Throwable $e) {
            self::assertThat($e, new ExceptionConstraint($type));

            if ($message !== null) {
                self::assertThat($e, new ExceptionMessageConstraint($message));
            }
        }
    }

}
