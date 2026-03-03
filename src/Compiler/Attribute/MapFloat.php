<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Attribute;

use Attribute;
use ShipMonk\InputMapper\Compiler\Mapper\Input\FloatInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class MapFloat implements InputMapperCompilerProvider
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

}
