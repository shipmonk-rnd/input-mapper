<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class Optional
{

    public function __construct(
        public readonly mixed $default = null,
    )
    {
    }

}
