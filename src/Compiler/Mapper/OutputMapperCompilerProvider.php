<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper;

use ShipMonk\InputMapper\Compiler\MapperFactory\MapperCompilerFactory;

interface OutputMapperCompilerProvider
{

    /**
     * @param array<string, mixed> $options
     */
    public function getOutputMapperCompiler(
        MapperCompilerFactory $mapperCompilerFactory,
        array $options,
    ): MapperCompiler;

}
