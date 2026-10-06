<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Runtime\Data;

use SensitiveParameter;

class SensitiveInput
{

    public function __construct(
        public readonly string $login,
        #[SensitiveParameter]
        public readonly string $password,
    )
    {
    }

}
