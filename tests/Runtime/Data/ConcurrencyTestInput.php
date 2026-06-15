<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Runtime\Data;

class ConcurrencyTestInput
{

    public function __construct(
        public readonly int $number,
    )
    {
    }

}
