<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Runtime\Data;

interface Duration
{

    public function toSeconds(): int;

}
