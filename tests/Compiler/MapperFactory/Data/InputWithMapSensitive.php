<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\MapperFactory\Data;

use SensitiveParameter;
use ShipMonk\InputMapper\Compiler\Attribute\MapSensitive;
use ShipMonk\InputMapper\Compiler\Attribute\MapString;
use ShipMonk\InputMapper\Compiler\Validator\String\AssertStringNonEmpty;

class InputWithMapSensitive
{

    public function __construct(
        #[MapSensitive(new MapString())]
        #[AssertStringNonEmpty]
        public readonly string $password,

        #[SensitiveParameter]
        #[MapSensitive(new MapString())]
        public readonly string $token,
    )
    {
    }

}
