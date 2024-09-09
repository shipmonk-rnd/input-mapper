<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Object\Data;

use ShipMonk\InputMapper\Runtime\Optional;

class HierarchicalChildTwoInput extends HierarchicalParentInput
{

    /**
     * @param  Optional<int> $age
     */
    public function __construct(
        int $id,
        string $name,
        Optional $age,
        string $type,
        public readonly int $childTwoField,
    )
    {
        parent::__construct($id, $name, $age, $type);
    }

}
