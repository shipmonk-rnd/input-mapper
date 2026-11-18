<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\MapperFactory\Data;

use SensitiveParameter;
use ShipMonk\InputMapper\Compiler\Attribute\Optional;
use ShipMonk\InputMapper\Compiler\Validator\String\AssertStringLength;

class InputWithSensitiveParameter
{

    public function __construct(
        #[SensitiveParameter]
        #[AssertStringLength(max: 64)]
        public readonly string $password,

        #[SensitiveParameter]
        #[Optional(default: '')]
        public readonly string $token,

        public readonly string $login,
    )
    {
    }

}
