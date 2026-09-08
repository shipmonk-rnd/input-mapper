<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Runtime\Data\Pregeneration;

class PriceInput
{

    public function __construct(
        public readonly int $amount,
        public readonly string $currency,
    )
    {
    }

}
