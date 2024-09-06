<?php declare(strict_types=1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Object\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Object\Discriminator;

#[Discriminator(
    'type',
    mapping: [
        HierarchicalWithEnumType::ChildOne->value => HierarchicalWithEnumChildInput::class,
    ],
)]
abstract class HierarchicalWithEnumParentInput
{

    public function __construct(
        public readonly int      $id,
        public readonly HierarchicalWithEnumType $type,
    )
    {
    }

}
