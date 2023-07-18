<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Runtime\Data\Optional;

use ShipMonk\InputMapper\Compiler\Validator\Int\AssertPositiveInt;
use ShipMonk\InputMapper\Runtime\Optional;

class OptionalNullableInput
{

    /**
     * @param Optional<?int> $number
     */
    public function __construct(
        #[AssertPositiveInt]
        public readonly Optional $number,
    )
    {
    }

}
