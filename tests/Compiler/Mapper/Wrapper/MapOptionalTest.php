<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Wrapper;

use ShipMonk\InputMapper\Compiler\Mapper\Input\IntInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Input\OptionalInputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Optional;
use ShipMonk\InputMapperTests\Compiler\Mapper\MapperCompilerTestCase;

class MapOptionalTest extends MapperCompilerTestCase
{

    public function testCompile(): void
    {
        $mapperCompiler = new OptionalInputMapperCompiler(new IntInputMapperCompiler());
        $mapper = $this->compileInputMapper('OptionalInt', $mapperCompiler);

        self::assertEquals(Optional::of(1), $mapper->map(1));
        self::assertEquals(Optional::of(2), $mapper->map(2));

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
