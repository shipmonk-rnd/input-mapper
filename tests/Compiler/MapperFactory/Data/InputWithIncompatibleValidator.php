<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\MapperFactory\Data;

use ShipMonk\InputMapper\Compiler\Validator\Int\AssertPositiveInt;

class InputWithIncompatibleValidator
{

    public function __construct(
        #[AssertPositiveInt]
        public readonly string $value,
    )
    {
    }

}
