<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Scalar;

use ShipMonk\InputMapper\Compiler\Mapper\Input\StringInputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapperTests\Compiler\Mapper\MapperCompilerTestCase;

class MapStringTest extends MapperCompilerTestCase
{

    public function testCompile(): void
    {
        $mapperCompiler = new StringInputMapperCompiler();
        $mapper = $this->compileInputMapper('String', $mapperCompiler);

        self::assertSame('', $mapper->map(''));
        self::assertSame('abc', $mapper->map('abc'));

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected string, got null',
            static fn () => $mapper->map(null),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected string, got 1',
            static fn () => $mapper->map(1),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected string, got array',
            static fn () => $mapper->map([]),
        );
    }

}
