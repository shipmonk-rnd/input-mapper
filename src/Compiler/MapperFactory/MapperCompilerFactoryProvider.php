<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\MapperFactory;

use ShipMonk\InputMapper\Runtime\CodecRegistry;

interface MapperCompilerFactoryProvider
{

    public function get(): MapperCompilerFactory;

    /**
     * Returns the codec registry used by the provided factory.
     */
    public function getCodecRegistry(): CodecRegistry;

}
