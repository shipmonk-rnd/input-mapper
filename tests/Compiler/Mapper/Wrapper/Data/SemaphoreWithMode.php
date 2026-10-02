<?php declare (strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Wrapper\Data;

use ShipMonk\InputMapper\Compiler\Attribute\Optional;

class SemaphoreWithMode
{

    public function __construct(
        #[Optional(default: SemaphoreModeEnum::Normal)]
        public readonly SemaphoreModeEnum $mode,
    )
    {
    }

}
