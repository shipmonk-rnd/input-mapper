<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Runtime\Data;

use DateTimeImmutable;

class EventInput
{

    public function __construct(
        public readonly string $name,
        public readonly DateTimeImmutable $date,
    )
    {
    }

}
