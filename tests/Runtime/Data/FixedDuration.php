<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Runtime\Data;

class FixedDuration implements Duration
{

    public function __construct(
        private readonly int $seconds,
    )
    {
    }

    public function toSeconds(): int
    {
        return $this->seconds;
    }

}
