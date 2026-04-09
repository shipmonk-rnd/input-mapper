<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Runtime\Data;

use DateTimeImmutable;

class NullableDateTimeInput
{

    public function __construct(
        public readonly ?DateTimeImmutable $date,
    )
    {
    }

}
