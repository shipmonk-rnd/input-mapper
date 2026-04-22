<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Runtime\Data;

class AcronymCasedInput
{

    public function __construct(
        public readonly string $HTTPServer,
        public readonly int $userIDValue,
    )
    {
    }

}
