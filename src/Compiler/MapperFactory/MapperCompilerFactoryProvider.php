<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\MapperFactory;

use ShipMonk\InputMapper\Runtime\CodecRegistry;

interface MapperCompilerFactoryProvider
{

    public function get(?CodecRegistry $codecRegistry = null): MapperCompilerFactory;

}
