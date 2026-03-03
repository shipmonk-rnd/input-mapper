<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Attribute;

use Attribute;
use ShipMonk\InputMapper\Compiler\Mapper\Input\OptionalInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Output\OptionalOutputMapperCompiler;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class MapOptional implements MapperCompilerProvider
{

    public function __construct(
        public readonly MapperCompilerProvider $mapperCompilerProvider,
    )
    {
    }

    public function getInputMapperCompiler(): MapperCompiler
    {
        return new OptionalInputMapperCompiler($this->mapperCompilerProvider->getInputMapperCompiler());
    }

    public function getOutputMapperCompiler(): MapperCompiler
    {
        return new OptionalOutputMapperCompiler($this->mapperCompilerProvider->getOutputMapperCompiler());
    }

}
