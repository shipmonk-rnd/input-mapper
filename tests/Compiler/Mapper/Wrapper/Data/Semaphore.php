<?php declare (strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Wrapper\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Optional;

class Semaphore
{

    public function __construct(
        #[Optional(default: SemaphoreColorEnum::Red)]
        public readonly SemaphoreColorEnum $color,

        #[Optional]
        public readonly ?string $manufacturer,
    )
    {
    }

}
