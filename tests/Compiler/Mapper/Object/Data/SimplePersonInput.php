<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data;

class SimplePersonInput
{

    public function __construct(
        public readonly int $id,
        public readonly string $name,
    )
    {
    }

}
