<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Runtime\Data;

class MoneyValue
{

    public function __construct(
        public readonly string $currency,
        public readonly int $cents,
    )
    {
    }

}
