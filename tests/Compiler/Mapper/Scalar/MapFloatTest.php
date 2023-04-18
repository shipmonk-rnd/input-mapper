<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Scalar;

use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapFloat;
use ShipMonk\InputMapper\Runtime\MappingFailedException;
use ShipMonkTests\InputMapper\Compiler\Mapper\MapperCompilerTestCase;

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
    }

}
