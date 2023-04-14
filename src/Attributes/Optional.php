<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Optional
{

}
