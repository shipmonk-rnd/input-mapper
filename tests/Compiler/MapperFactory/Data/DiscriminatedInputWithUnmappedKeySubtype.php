<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\MapperFactory\Data;

class DiscriminatedInputWithUnmappedKeySubtype extends DiscriminatedInputWithUnmappedKey
{

    public function __construct(public readonly string $name)
    {
    }

}
