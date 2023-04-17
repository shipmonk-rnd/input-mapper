<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Tests\Runtime\Data\Optional;

use ShipMonk\InputMapper\Runtime\Optional;

class OptionalNullableInput
{

    /**
     * @param Optional<?int> $number
     */
    public function __construct(
        public readonly Optional $number,
    )
    {
    }

}
