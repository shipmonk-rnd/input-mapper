<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper;

interface InputMapperCompilerProvider
{

    public function getInputMapperCompiler(): MapperCompiler;

}
