<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper;

use ShipMonk\InputMapper\Compiler\MapperFactory\MapperCompilerFactory;

interface InputMapperCompilerProvider
{

    /**
     * @param array<string, mixed> $options
     */
    public function getInputMapperCompiler(
        MapperCompilerFactory $mapperCompilerFactory,
        array $options,
    ): MapperCompiler;

}
