<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Object;

use ShipMonk\InputMapper\Compiler\Mapper\Input\EnumInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Input\StringInputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapperTests\Compiler\Mapper\MapperCompilerTestCase;
use ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data\SuitEnum;

class MapEnumTest extends MapperCompilerTestCase
{

    public function testCompile(): void
    {
        $mapperCompiler = new EnumInputMapperCompiler(SuitEnum::class, new StringInputMapperCompiler());
        $mapper = $this->compileMapper('SuitEnum', $mapperCompiler);

        self::assertSame(SuitEnum::Clubs, $mapper->map('C'));
        self::assertSame(SuitEnum::Spades, $mapper->map('S'));

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected one of H, D, C, S, got "X"',
            static fn () => $mapper->map('X'),
        );
    }

}
