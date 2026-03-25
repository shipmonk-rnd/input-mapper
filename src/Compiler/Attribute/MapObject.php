<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Attribute;

use ShipMonk\InputMapper\Compiler\Mapper\Input\ObjectInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Output\ObjectOutputMapperCompiler;

class MapObject implements MapperCompilerProvider
{

    /**
     * @param ObjectInputMapperCompiler<object> $inputMapperCompiler
     * @param ObjectOutputMapperCompiler<object> $outputMapperCompiler
     */
    public function __construct(
        public readonly ObjectInputMapperCompiler $inputMapperCompiler,
        public readonly ObjectOutputMapperCompiler $outputMapperCompiler,
    )
    {
    }

    public function getInputMapperCompiler(): MapperCompiler
    {
        return $this->inputMapperCompiler;
    }

    public function getOutputMapperCompiler(): MapperCompiler
    {
        return $this->outputMapperCompiler;
    }

}
