<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Runtime\Data;

class OrderInput
{

    public function __construct(
        public readonly int $id,
        public readonly MoneyValue $total,
    )
    {
    }

}
