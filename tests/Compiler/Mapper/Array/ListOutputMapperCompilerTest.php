<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Array;

use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use ShipMonk\InputMapper\Compiler\Mapper\Output\ListOutputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\PassthroughMapperCompiler;
use ShipMonk\InputMapperTests\Compiler\Mapper\OutputMapperCompilerTestCase;

class ListOutputMapperCompilerTest extends OutputMapperCompilerTestCase
{

    public function testCompile(): void
    {
        $itemMapperCompiler = new PassthroughMapperCompiler(new IdentifierTypeNode('int'));
        $mapperCompiler = new ListOutputMapperCompiler($itemMapperCompiler);
        $mapper = $this->compileOutputMapper('ListOfInts', $mapperCompiler);

        self::assertSame([], $mapper->map([]));
        self::assertSame([1], $mapper->map([1]));
        self::assertSame([1, 2, 3], $mapper->map([1, 2, 3]));
    }

}
