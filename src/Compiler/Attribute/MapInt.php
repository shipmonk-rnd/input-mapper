<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Attribute;

use Attribute;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use ShipMonk\InputMapper\Compiler\Mapper\Input\IntInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\PassthroughMapperCompiler;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class MapInt implements MapperCompilerProvider
{

    public function getInputMapperCompiler(): MapperCompiler
    {
        return new IntInputMapperCompiler();
    }

    public function getOutputMapperCompiler(): MapperCompiler
    {
        return new PassthroughMapperCompiler(new IdentifierTypeNode('int'));
    }

}
