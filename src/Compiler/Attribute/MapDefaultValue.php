<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Attribute;

use Attribute;
use ShipMonk\InputMapper\Compiler\Mapper\Input\DefaultValueInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class MapDefaultValue implements MapperCompilerProvider
{

    public function __construct(
        public readonly MapperCompilerProvider $mapperCompilerProvider,
        public readonly mixed $defaultValue,
    )
    {
    }

    public function getInputMapperCompiler(): MapperCompiler
    {
        return new DefaultValueInputMapperCompiler($this->mapperCompilerProvider->getInputMapperCompiler(), $this->defaultValue);
    }

    public function getOutputMapperCompiler(): MapperCompiler
    {
        return $this->mapperCompilerProvider->getOutputMapperCompiler();
    }

}
