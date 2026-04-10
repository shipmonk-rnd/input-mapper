<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Scalar;

use ShipMonk\InputMapper\Compiler\Mapper\Input\BoolInputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapperTests\Compiler\Mapper\MapperCompilerTestCase;

class MapBoolTest extends MapperCompilerTestCase
{

    public function testCompile(): void
    {
        $mapperCompiler = new BoolInputMapperCompiler();
        $mapper = $this->compileInputMapper('Bool', $mapperCompiler);

        self::assertTrue($mapper->map(true));
        self::assertFalse($mapper->map(false));

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected bool, got null',
            static fn () => $mapper->map(null),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected bool, got 1',
            static fn () => $mapper->map(1),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected bool, got array',
            static fn () => $mapper->map([]),
        );
    }

}
