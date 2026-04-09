<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper;

interface OutputMapperCompilerProvider
{

    public function getOutputMapperCompiler(): MapperCompiler;

}
