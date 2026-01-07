<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data;

use ShipMonk\InputMapper\Runtime\Optional;

class PersonInput
{

    /**
     * @param  Optional<int> $age
     */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly Optional $age,
    )
    {
    }

}
