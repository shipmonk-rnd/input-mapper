<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Object\Data;

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
