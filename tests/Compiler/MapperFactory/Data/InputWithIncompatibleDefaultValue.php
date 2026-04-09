<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\MapperFactory\Data;

use ShipMonk\InputMapper\Compiler\Attribute\Optional;

class InputWithIncompatibleDefaultValue
{

    public function __construct(
        #[Optional(default: 'not_an_int')]
        public readonly int $value,
    )
    {
    }

}
