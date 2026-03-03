<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Attribute;

use Attribute;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use ShipMonk\InputMapper\Compiler\Mapper\Input\FloatInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\PassthroughMapperCompiler;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class MapFloat implements InputMapperCompilerProvider, OutputMapperCompilerProvider
{

    public function __construct(
        public readonly bool $allowInfinity = false,
        public readonly bool $allowNan = false,
    )
    {
    }

    public function getInputMapperCompiler(): MapperCompiler
    {
        return new FloatInputMapperCompiler($this->allowInfinity, $this->allowNan);
    }

    public function getOutputMapperCompiler(): MapperCompiler
    {
        return new PassthroughMapperCompiler(new IdentifierTypeNode('float'));
    }

}
