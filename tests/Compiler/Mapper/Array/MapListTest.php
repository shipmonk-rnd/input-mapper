<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Array;

use ShipMonk\InputMapper\Compiler\Mapper\Input\IntInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Input\ListInputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapperTests\Compiler\Mapper\MapperCompilerTestCase;

class MapListTest extends MapperCompilerTestCase
{

    public function testCompile(): void
    {
        $itemMapperCompiler = new IntInputMapperCompiler();
        $mapperCompiler = new ListInputMapperCompiler($itemMapperCompiler);
        $mapper = $this->compileMapper('GenericList', $mapperCompiler);

        self::assertSame([], $mapper->map([]));
        self::assertSame([1], $mapper->map([1]));
        self::assertSame([1, 2, 3], $mapper->map([1, 2, 3]));

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected list, got "1"',
            static fn () => $mapper->map('1'),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected list, got null',
            static fn () => $mapper->map(null),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /1: Expected int, got "2"',
            static fn () => $mapper->map([1, '2']),
        );
    }

}
