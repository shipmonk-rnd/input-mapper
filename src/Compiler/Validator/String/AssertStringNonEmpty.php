<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Validator\String;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class AssertStringNonEmpty extends AssertStringMatches
{

    public function __construct()
    {
        parent::__construct(
            pattern: '#\S#',
            expectedDescription: 'non-empty string',
        );
    }

}
