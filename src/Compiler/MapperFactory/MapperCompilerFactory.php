<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\MapperFactory;

use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;

interface MapperCompilerFactory
{

    /**
     * @param  class-string $className
     */
    public function createObjectMapperCompiler(string $className): MapperCompiler;

}
