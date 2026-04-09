<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Attribute;

use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use ShipMonk\InputMapper\Compiler\Attribute\MapChain;
use ShipMonk\InputMapper\Compiler\Attribute\MapInt;
use ShipMonk\InputMapper\Compiler\Attribute\MapString;
use ShipMonk\InputMapper\Compiler\Mapper\Input\ChainMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Input\IntInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Input\StringInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\PassthroughMapperCompiler;
use ShipMonk\InputMapperTests\InputMapperTestCase;

class MapChainTest extends InputMapperTestCase
{

    public function testGetInputMapperCompiler(): void
    {
        $mapChain = new MapChain([new MapString(), new MapInt()]);

        $result = $mapChain->getInputMapperCompiler();

        self::assertEquals(
            new ChainMapperCompiler([new StringInputMapperCompiler(), new IntInputMapperCompiler()]),
            $result,
        );
    }

    public function testGetOutputMapperCompiler(): void
    {
        $mapChain = new MapChain([new MapString(), new MapInt()]);

        $result = $mapChain->getOutputMapperCompiler();

        self::assertEquals(
            new ChainMapperCompiler([
                new PassthroughMapperCompiler(new IdentifierTypeNode('int')),
                new PassthroughMapperCompiler(new IdentifierTypeNode('string')),
            ]),
            $result,
        );
    }

}
