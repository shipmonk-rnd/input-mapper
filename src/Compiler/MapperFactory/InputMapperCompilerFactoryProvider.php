<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\MapperFactory;

interface InputMapperCompilerFactoryProvider
{

    public function get(): MapperCompilerFactory;

}
