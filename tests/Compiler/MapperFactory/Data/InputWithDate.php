<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\MapperFactory\Data;

use DateTimeImmutable;
use ShipMonk\InputMapper\Compiler\Mapper\Object\MapDateTimeImmutable;

class InputWithDate
{

    public function __construct(
        #[MapDateTimeImmutable('Y-m-d', 'date string in Y-m-d format')]
        public readonly DateTimeImmutable $date,
        public readonly DateTimeImmutable $dateTime,
    )
    {
    }

}
