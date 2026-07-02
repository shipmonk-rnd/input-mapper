<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\MapperFactory\Data;

class InputWithConstantIntRange
{

    /**
     * @param int<1, IntRangeLimit::MAX> $quantity
     */
    public function __construct(
        public readonly int $quantity,
    )
    {
    }

}
