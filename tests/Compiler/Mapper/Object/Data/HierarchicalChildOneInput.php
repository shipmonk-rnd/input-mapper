<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data;

use ShipMonk\InputMapper\Runtime\Optional;

class HierarchicalChildOneInput extends HierarchicalParentInput
{

    /**
     * @param  Optional<int> $age
     */
    public function __construct(
        int $id,
        string $name,
        Optional $age,
        string $type,
        public readonly string $childOneField,
    )
    {
        parent::__construct($id, $name, $age, $type);
    }

}
