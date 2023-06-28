<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Validator\Float;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class AssertPositiveFloat extends AssertFloatRange
{

    public function __construct()
    {
        parent::__construct(
            gt: 0.0,
        );
    }

}
