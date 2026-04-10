<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Scalar;

use ShipMonk\InputMapper\Compiler\Mapper\Input\IntInputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapperTests\Compiler\Mapper\MapperCompilerTestCase;

class MapIntTest extends MapperCompilerTestCase
{

    public function testCompile(): void
    {
        $mapperCompiler = new IntInputMapperCompiler();
        $mapper = $this->compileInputMapper('Int', $mapperCompiler);

        self::assertSame(1, $mapper->map(1));
        self::assertSame(2, $mapper->map(2));

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected int, got "1"',
            static fn () => $mapper->map('1'),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected int, got null',
            static fn () => $mapper->map(null),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected int, got array',
            static fn () => $mapper->map([]),
        );
    }

}
