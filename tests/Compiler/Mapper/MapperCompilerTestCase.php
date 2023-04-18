<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper;

use Nette\Utils\FileSystem;
use Nette\Utils\Json;
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
     * @param  array<class-string, Mapper<mixed>> $mappers
     * @return Mapper<mixed>
     */
    protected function compileMapper(
        string $name,
        MapperCompiler $mapperCompiler,
        array $mappers = [],
    ): Mapper
    {
        $testCaseReflection = new ReflectionClass($this);

        $mapperShortClassName = ucfirst($name) . 'Mapper';
        $mapperNamespace = $testCaseReflection->getNamespaceName() . '\\Data';
        $mapperClassName = "{$mapperNamespace}\\{$mapperShortClassName}";

        $mapperDir = strtr(str_replace(__NAMESPACE__, __DIR__, $mapperNamespace), '\\', '/');
        $mapperPath = "{$mapperDir}/{$mapperShortClassName}.php";
        $jsonSchemaPath = "{$mapperDir}/{$mapperShortClassName}.json";

        if (!class_exists($mapperClassName, autoload: false)) {
            $generator = new Generator();
            self::assertSnapshot($mapperPath, $generator->generateMapperFile($mapperClassName, $mapperCompiler));
            require $mapperPath;

            $jsonSchema = Json::encode($mapperCompiler->getJsonSchema(), pretty: true) . "\n";
            self::assertSnapshot($jsonSchemaPath, $jsonSchema);
        }

        $mapperProvider = $this->createMock(MapperProvider::class);

        foreach ($mappers as $inputClassName => $mapper) {
            $mapperProvider->expects(self::any())->method('get')->with($inputClassName)->willReturn($mapper);
        }

        $mapper = new $mapperClassName($mapperProvider);
        assert($mapper instanceof Mapper);

        return $mapper;
    }

    protected static function assertSnapshot(string $snapshotPath, string $actual): void
    {
        if (is_file($snapshotPath) && getenv('UPDATE_SNAPSHOTS') === false) {
            $expected = FileSystem::read($snapshotPath);
            self::assertSame($expected, $actual);

        } elseif (getenv('CI') === false) {
            FileSystem::write($snapshotPath, $actual);

        } else {
            self::fail("Snapshot file {$snapshotPath} does not exist. Run tests locally to generate it.");
        }
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
