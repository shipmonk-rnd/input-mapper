<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Attribute;

use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;

interface InputMapperCompilerProvider
{

    public function getInputMapperCompiler(): MapperCompiler;

}
