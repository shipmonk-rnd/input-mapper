<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\MapperFactory;

interface MapperCompilerFactoryProvider
{

    public function get(): MapperCompilerFactory;

}
