<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Runtime\Data;

use DateTimeImmutable;
use ShipMonk\InputMapper\Compiler\Attribute\MapNullable;
use ShipMonk\InputMapper\Compiler\Attribute\Optional;

class SpecialRequirementsDatesInput
{

    public function __construct(
        #[DateTimeFormatCodec(format: 'Y-m-d')]
        public readonly DateTimeImmutable $deadline,
        #[Optional]
        #[MapNullable(new DateTimeFormatCodec(format: 'Y-m-d\TH:i:sP'))]
        public readonly ?DateTimeImmutable $earliest = null,
    )
    {
    }

}
