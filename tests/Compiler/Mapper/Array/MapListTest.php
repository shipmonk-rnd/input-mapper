<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Array;

use ShipMonk\InputMapper\Compiler\Mapper\Array\MapList;
use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapInt;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonkTests\InputMapper\Compiler\Mapper\MapperCompilerTestCase;

class MapListTest extends MapperCompilerTestCase
{

    public function testCompile(): void
    {
        $itemMapperCompiler = new MapInt();
        $mapperCompiler = new MapList($itemMapperCompiler);
        $mapper = $this->compileMapper('GenericList', $mapperCompiler);

        self::assertSame([], $mapper->map([]));
        self::assertSame([1], $mapper->map([1]));
        self::assertSame([1, 2, 3], $mapper->map([1, 2, 3]));

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected list, got "1"',
            static fn() => $mapper->map('1'),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected list, got null',
            static fn() => $mapper->map(null),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /1: Expected int, got "2"',
            static fn() => $mapper->map([1, '2']),
        );
    }

}
