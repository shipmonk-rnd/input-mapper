<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Runtime\Data;

use ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data\SuitEnum;

class CardInput
{

    public function __construct(
        public readonly SuitEnum $suit,
        public readonly int $value,
    )
    {
    }

}
