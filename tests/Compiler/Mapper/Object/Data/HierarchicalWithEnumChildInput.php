<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Object\Data;

class HierarchicalWithEnumChildInput extends HierarchicalWithEnumParentInput
{

    public function __construct(
        int $id,
        HierarchicalWithEnumType $type,
    )
    {
        parent::__construct($id, $type);
    }

}
