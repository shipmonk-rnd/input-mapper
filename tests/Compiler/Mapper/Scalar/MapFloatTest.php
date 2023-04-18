<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Scalar;

use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapFloat;
use ShipMonk\InputMapper\Runtime\MappingFailedException;
use ShipMonkTests\InputMapper\Compiler\Mapper\MapperCompilerTestCase;
use const INF;
use const NAN;

class MapFloatTest extends MapperCompilerTestCase
{

    public function testCompile(): void
    {
        $mapperCompiler = new MapFloat();
        $mapper = $this->compileMapper('Float', $mapperCompiler);

        self::assertSame(1.0, $mapper->map(1.0));
        self::assertSame(1.2, $mapper->map(1.2));
        self::assertSame(1.0, $mapper->map(1));

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected float, got null',
            static fn() => $mapper->map(null),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected float, got "a"',
            static fn() => $mapper->map('a'),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected float, got array',
            static fn() => $mapper->map([]),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected finite float, got INF',
            static fn() => $mapper->map(+INF),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected finite float, got -INF',
            static fn() => $mapper->map(-INF),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected finite float, got NAN',
            static fn() => $mapper->map(NAN),
        );
    }

    public function testCompileWithAllowedInfinity(): void
    {
        $mapperCompiler = new MapFloat(allowInfinity: true);
        $mapper = $this->compileMapper('FloatWithAllowedInfinity', $mapperCompiler);

        self::assertSame(1.0, $mapper->map(1.0));
        self::assertSame(1.0, $mapper->map(1));

        self::assertSame(+INF, $mapper->map(+INF));
        self::assertSame(-INF, $mapper->map(-INF));

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected finite float or INF, got NAN',
            static fn() => $mapper->map(NAN),
        );
    }

    public function testCompileWithAllowedNan(): void
    {
        $mapperCompiler = new MapFloat(allowNan: true);
        $mapper = $this->compileMapper('FloatWithAllowedNan', $mapperCompiler);

        self::assertSame(1.0, $mapper->map(1.0));
        self::assertSame(1.0, $mapper->map(1));

        self::assertNan($mapper->map(NAN));

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected finite float or NAN, got INF',
            static fn() => $mapper->map(+INF),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected finite float or NAN, got -INF',
            static fn() => $mapper->map(-INF),
        );
    }

    public function testCompileWithAllowedInfinityAndNan(): void
    {
        $mapperCompiler = new MapFloat(allowInfinity: true, allowNan: true);
        $mapper = $this->compileMapper('FloatWithAllowedInfinityAndNan', $mapperCompiler);

        self::assertSame(1.0, $mapper->map(1.0));
        self::assertSame(1.0, $mapper->map(1));

        self::assertSame(+INF, $mapper->map(+INF));
        self::assertSame(-INF, $mapper->map(-INF));

        self::assertNan($mapper->map(NAN));
    }

}
