<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Runtime\Data;

use ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data\SuitEnum;

class NullableEnumInput
{

    public function __construct(
        public readonly ?SuitEnum $suit,
    )
    {
    }

}
