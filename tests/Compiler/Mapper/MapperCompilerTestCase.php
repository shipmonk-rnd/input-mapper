<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper;

use PHPUnit\Framework\Constraint\Exception as ExceptionConstraint;
use PHPUnit\Framework\Constraint\ExceptionMessage as ExceptionMessageConstraint;
use PHPUnit\Framework\TestCase;
use ShipMonk\InputMapper\Compiler\Generator;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use Throwable;
use function assert;
use function bin2hex;
use function random_bytes;
use function strlen;
use function substr;

abstract class MapperCompilerTestCase extends TestCase
{

    /**
     * @return Mapper<mixed>
     */
    protected function compileMapper(
        MapperCompiler $mapperCompiler,
        ?MapperProvider $mapperProvider = null
    ): Mapper
    {
        $mapperClassName = __NAMESPACE__ . '\\MapCompilerTest_' . bin2hex(random_bytes(8));

        $generator = new Generator();
        $mapperCode = $generator->generateMapperFile($mapperClassName, $mapperCompiler);
        eval(substr($mapperCode, strlen('<?php')));

        $mapperProvider ??= $this->createMock(MapperProvider::class);
        $mapper = new $mapperClassName($mapperProvider);
        assert($mapper instanceof Mapper);

        return $mapper;
    }

    /**
     * @template T of Throwable
     * @param  class-string<T>  $type
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
