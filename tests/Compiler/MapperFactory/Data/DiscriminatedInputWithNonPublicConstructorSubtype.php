<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\MapperFactory\Data;

class DiscriminatedInputWithNonPublicConstructorSubtype extends DiscriminatedInputWithNonPublicConstructor
{

    private function __construct()
    {
    }

}
