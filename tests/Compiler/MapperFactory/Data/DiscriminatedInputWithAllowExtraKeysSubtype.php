<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\MapperFactory\Data;

use ShipMonk\InputMapper\Compiler\Attribute\AllowExtraKeys;

#[AllowExtraKeys]
class DiscriminatedInputWithAllowExtraKeysSubtype extends DiscriminatedInputWithAllowExtraKeys
{

    public function __construct(public readonly string $name)
    {
    }

}
