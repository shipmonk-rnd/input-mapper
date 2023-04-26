<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Validator\Float;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class AssertNonNegativeFloat extends AssertFloatRange
{

    public function __construct()
    {
        parent::__construct(
            gte: 0.0,
        );
    }

}
