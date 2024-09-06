<?php declare(strict_types=1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Object\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Object\Discriminator;
use ShipMonk\InputMapper\Runtime\Optional;

#[Discriminator(
    'type',
    mapping: [
        'childOne' => HierarchicalChildOneInput::class,
        'childTwo' => HierarchicalChildTwoInput::class,
    ],
)]
abstract class HierarchicalParentInput
{

    /**
     * @param Optional<int> $age
     */
    public function __construct(
        public readonly int      $id,
        public readonly string   $name,
        public readonly Optional $age,
        public readonly string $type,
    )
    {
    }

}
