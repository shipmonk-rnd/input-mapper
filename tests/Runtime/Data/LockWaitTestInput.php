<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Runtime\Data;

class LockWaitTestInput
{

    public function __construct(
        public readonly int $number,
    )
    {
    }

}
