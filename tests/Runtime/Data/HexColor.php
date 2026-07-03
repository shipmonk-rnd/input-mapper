<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Runtime\Data;

class HexColor
{

    public function __construct(
        public readonly string $hex,
    )
    {
    }

}
