<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Attribute;

use Attribute;
use ShipMonk\InputMapper\Compiler\Mapper\Input\OptionalInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class MapOptional implements InputMapperCompilerProvider
{

    public function __construct(
        public readonly MapperCompiler $mapperCompiler,
    )
    {
    }

    public function getInputMapperCompiler(): MapperCompiler
    {
        return new OptionalInputMapperCompiler($this->mapperCompiler);
    }

}
