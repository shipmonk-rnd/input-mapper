<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Array;

use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use ShipMonk\InputMapper\Compiler\Mapper\Output\EnumOutputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Output\ListOutputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\PassthroughMapperCompiler;
use ShipMonk\InputMapperTests\Compiler\Mapper\MapperCompilerTestCase;
use ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data\SuitEnum;

class ListOutputMapperCompilerTest extends MapperCompilerTestCase
{

    public function testCompilePassthrough(): void
    {
        $itemMapperCompiler = new PassthroughMapperCompiler(new IdentifierTypeNode('int'));
        $mapperCompiler = new ListOutputMapperCompiler($itemMapperCompiler);
        $mapper = $this->compileOutputMapper('ListOfInts', $mapperCompiler);

        self::assertSame([], $mapper->map([]));
        self::assertSame([1], $mapper->map([1]));
        self::assertSame([1, 2, 3], $mapper->map([1, 2, 3]));
    }

    public function testCompileWithItemMapper(): void
    {
        $itemMapperCompiler = new EnumOutputMapperCompiler(SuitEnum::class);
        $mapperCompiler = new ListOutputMapperCompiler($itemMapperCompiler);
        $mapper = $this->compileOutputMapper('ListOfEnums', $mapperCompiler);

        self::assertSame([], $mapper->map([]));
        self::assertSame(['H'], $mapper->map([SuitEnum::Hearts]));
        self::assertSame(['H', 'D', 'C', 'S'], $mapper->map([SuitEnum::Hearts, SuitEnum::Diamonds, SuitEnum::Clubs, SuitEnum::Spades]));
    }

}
