<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\MapperFactory;

interface OutputMapperCompilerFactoryProvider
{

    public function get(): MapperCompilerFactory;

}
