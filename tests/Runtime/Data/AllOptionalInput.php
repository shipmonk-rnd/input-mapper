<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Runtime\Data;

use ShipMonk\InputMapper\Runtime\Optional;

class AllOptionalInput
{

    /**
     * @param Optional<int> $a
     * @param Optional<string> $b
     */
    public function __construct(
        public readonly Optional $a,
        public readonly Optional $b,
    )
    {
    }

}
