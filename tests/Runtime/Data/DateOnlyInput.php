<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Runtime\Data;

use DateTimeImmutable;
use ShipMonk\InputMapper\Compiler\Attribute\MapDate;

class DateOnlyInput
{

    public function __construct(
        #[MapDate]
        public readonly DateTimeImmutable $date,
    )
    {
    }

}
