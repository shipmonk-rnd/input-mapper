<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Runtime\Data\Pregeneration;

class AddressInput
{

    public function __construct(
        public readonly string $city,
    )
    {
    }

}
