<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Attribute;

use Attribute;
use ShipMonk\InputMapper\Compiler\Mapper\Input\ArrayInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompilerProvider;
use ShipMonk\InputMapper\Compiler\Mapper\Output\ArrayOutputMapperCompiler;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class MapArray implements MapperCompilerProvider
{

    public function __construct(
        public readonly MapperCompilerProvider $keyMapperCompilerProvider,
        public readonly MapperCompilerProvider $valueMapperCompilerProvider,
    )
    {
    }

    public function getInputMapperCompiler(): MapperCompiler
    {
        return new ArrayInputMapperCompiler(
            $this->keyMapperCompilerProvider->getInputMapperCompiler(),
            $this->valueMapperCompilerProvider->getInputMapperCompiler(),
        );
    }

    public function getOutputMapperCompiler(): MapperCompiler
    {
        return new ArrayOutputMapperCompiler(
            $this->keyMapperCompilerProvider->getOutputMapperCompiler(),
            $this->valueMapperCompilerProvider->getOutputMapperCompiler(),
        );
    }

}
