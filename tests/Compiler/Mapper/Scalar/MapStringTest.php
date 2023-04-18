<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Scalar;

use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapString;
use ShipMonk\InputMapper\Runtime\MappingFailedException;
use ShipMonkTests\InputMapper\Compiler\Mapper\MapperCompilerTestCase;

class MapStringTest extends MapperCompilerTestCase
{

    public function testCompile(): void
    {
        $mapperCompiler = new MapString();
        $mapper = $this->compileMapper('String', $mapperCompiler);

        self::assertSame('', $mapper->map(''));
        self::assertSame('abc', $mapper->map('abc'));

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected string, got null',
            static fn() => $mapper->map(null),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected string, got 1',
            static fn() => $mapper->map(1),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected string, got array',
            static fn() => $mapper->map([]),
        );
    }

}
