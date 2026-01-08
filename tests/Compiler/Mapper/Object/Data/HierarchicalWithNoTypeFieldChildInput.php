<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data;

class HierarchicalWithNoTypeFieldChildInput extends HierarchicalWithNoTypeFieldParentInput
{

    public function __construct(
        int $id,
        public readonly string $childOneField,
    )
    {
        parent::__construct($id);
    }

}
