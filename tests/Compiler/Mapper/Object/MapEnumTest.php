<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Object;

use ShipMonk\InputMapper\Compiler\Mapper\Object\MapEnum;
use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapString;
use ShipMonk\InputMapper\Runtime\MappingFailedException;
use ShipMonkTests\InputMapper\Compiler\Mapper\MapperCompilerTestCase;
use ShipMonkTests\InputMapper\Compiler\Mapper\Object\Data\SuitEnum;

class MapEnumTest extends MapperCompilerTestCase
{

    public function testCompile(): void
    {
        $mapperCompiler = new MapEnum(SuitEnum::class, new MapString());
        $mapper = $this->compileMapper('SuitEnum', $mapperCompiler);

        self::assertSame(SuitEnum::Clubs, $mapper->map('C'));
        self::assertSame(SuitEnum::Spades, $mapper->map('S'));

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected one of H, D, C, S, got "X"',
            static fn() => $mapper->map('X'),
        );
    }

}
