<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\MapperFactory\Data;

use ShipMonk\InputMapper\Compiler\Attribute\AllowExtraKeys;

#[AllowExtraKeys]
class DiscriminatedInputWithNestedDiscriminatorLeaf extends DiscriminatedInputWithNestedDiscriminatorSubtype
{

    public function __construct(public readonly string $name)
    {
    }

}
