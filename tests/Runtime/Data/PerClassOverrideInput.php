<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Runtime\Data;

class PerClassOverrideInput
{

    public function __construct(
        public readonly int $userId,
        public readonly string $firstName,
    )
    {
    }

}
