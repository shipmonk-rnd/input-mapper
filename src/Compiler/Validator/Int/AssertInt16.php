<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Validator\Int;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class AssertInt16 extends AssertIntRange
{

    public function __construct()
    {
        parent::__construct(
            gte: -32_768,
            lte: +32_767,
        );
    }

}
