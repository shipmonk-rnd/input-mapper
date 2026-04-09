<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data;

use ShipMonk\InputMapper\Compiler\Attribute\Discriminator;

#[Discriminator(
    '$type',
    mapping: [
        'childOne' => HierarchicalWithNoTypeFieldChildInput::class,
    ],
)]
abstract class HierarchicalWithNoTypeFieldParentInput
{

    public function __construct(
        public readonly int $id,
    )
    {
    }

}
