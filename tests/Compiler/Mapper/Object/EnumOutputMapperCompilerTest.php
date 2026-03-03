<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Object;

use ShipMonk\InputMapper\Compiler\Mapper\Output\EnumOutputMapperCompiler;
use ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data\SuitEnum;
use ShipMonk\InputMapperTests\Compiler\Mapper\OutputMapperCompilerTestCase;

class EnumOutputMapperCompilerTest extends OutputMapperCompilerTestCase
{

    public function testCompile(): void
    {
        $mapperCompiler = new EnumOutputMapperCompiler(SuitEnum::class);
        $mapper = $this->compileOutputMapper('SuitEnum', $mapperCompiler);

        self::assertSame('H', $mapper->map(SuitEnum::Hearts));
        self::assertSame('D', $mapper->map(SuitEnum::Diamonds));
        self::assertSame('C', $mapper->map(SuitEnum::Clubs));
        self::assertSame('S', $mapper->map(SuitEnum::Spades));
    }

}
