<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Validator\Int;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class AssertInt32 extends AssertIntRange
{

    public function __construct()
    {
        parent::__construct(
            gte: -2_147_483_648,
            lte: +2_147_483_647,
        );
    }

}
