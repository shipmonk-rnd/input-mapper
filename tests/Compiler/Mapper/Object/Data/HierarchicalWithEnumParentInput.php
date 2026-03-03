<?php declare(strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data;

use ShipMonk\InputMapper\Compiler\Attribute\Discriminator;

#[Discriminator(
    'type',
    mapping: [
        'childOne' => HierarchicalWithEnumChildInput::class,
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
