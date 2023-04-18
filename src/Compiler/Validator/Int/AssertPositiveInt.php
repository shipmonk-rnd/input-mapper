<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Validator\Int;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class AssertPositiveInt extends AssertIntRange
{

    public function __construct()
    {
        parent::__construct(
            gt: 0,
        );
    }

}
