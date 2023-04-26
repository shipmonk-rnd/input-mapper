<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Validator\Float;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class AssertNegativeFloat extends AssertFloatRange
{

    public function __construct()
    {
        parent::__construct(
            lt: 0.0,
        );
    }

}
