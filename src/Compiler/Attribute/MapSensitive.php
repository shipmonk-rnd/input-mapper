<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Attribute;

use Attribute;
use ShipMonk\InputMapper\Compiler\Mapper\Input\SensitiveInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompilerProvider;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class MapSensitive implements MapperCompilerProvider
{

    public function __construct(
        public readonly MapperCompilerProvider $innerMapperCompilerProvider,
    )
    {
    }

    public function getInputMapperCompiler(): MapperCompiler
    {
        return new SensitiveInputMapperCompiler($this->innerMapperCompilerProvider->getInputMapperCompiler());
    }

    /**
     * Output mapping starts from an already validated object, so there is nothing to redact
     */
    public function getOutputMapperCompiler(): MapperCompiler
    {
        return $this->innerMapperCompilerProvider->getOutputMapperCompiler();
    }

}
