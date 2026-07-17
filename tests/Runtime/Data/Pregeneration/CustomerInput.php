<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Runtime\Data\Pregeneration;

class CustomerInput
{

    public function __construct(
        public readonly string $name,
        public readonly AddressInput $address,
    )
    {
    }

}
