<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data;

class PersonWithNullableAgeInput
{

    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly ?int $age,
    )
    {
    }

}
