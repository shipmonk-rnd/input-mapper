<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Attribute;

use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;

interface OutputMapperCompilerProvider
{

    public function getOutputMapperCompiler(): MapperCompiler;

}
