<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Attribute;

use Attribute;
use ShipMonk\InputMapper\Compiler\Mapper\Input\NullableInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class MapNullable implements InputMapperCompilerProvider
{

    public function __construct(
        public readonly MapperCompiler $innerMapperCompiler,
    )
    {
    }

    public function getInputMapperCompiler(): MapperCompiler
    {
        return new NullableInputMapperCompiler($this->innerMapperCompiler);
    }

}
