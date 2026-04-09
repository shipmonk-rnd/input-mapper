<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Runtime\Data;

class EdgeValuesInput
{

    public function __construct(
        public readonly int $zero,
        public readonly string $emptyString,
        public readonly bool $false,
        public readonly float $zeroFloat,
    )
    {
    }

}
