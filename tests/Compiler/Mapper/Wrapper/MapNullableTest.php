<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Wrapper;

use ShipMonk\InputMapper\Compiler\Mapper\Mixed\MapMixed;
use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapInt;
use ShipMonk\InputMapper\Compiler\Mapper\Wrapper\MapNullable;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonkTests\InputMapper\Compiler\Mapper\MapperCompilerTestCase;

class MapNullableTest extends MapperCompilerTestCase
{

    public function testCompile(): void
    {
        $mapperCompiler = new MapNullable(new MapInt());
        $mapper = $this->compileMapper('NullableInt', $mapperCompiler);

        self::assertNull($mapper->map(null));
        self::assertSame(1, $mapper->map(1));
        self::assertSame(2, $mapper->map(2));

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected int, got "1"',
            static fn() => $mapper->map('1'),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected int, got array',
            static fn() => $mapper->map([]),
        );
    }

    public function testCompileWithMixed(): void
    {
        $mapperCompiler = new MapNullable(new MapMixed());
        $mapper = $this->compileMapper('NullableMixed', $mapperCompiler);

        self::assertNull($mapper->map(null));
        self::assertSame(1, $mapper->map(1));
        self::assertSame('A', $mapper->map('A'));
    }

}
