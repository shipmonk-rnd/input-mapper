<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\MapperFactory\Data;

use ShipMonk\InputMapper\Compiler\Attribute\AllowExtraKeys;
use ShipMonk\InputMapper\Compiler\Attribute\Discriminator;

#[Discriminator('type', ['dog' => DiscriminatedInputWithAllowExtraKeysSubtype::class])]
#[AllowExtraKeys]
abstract class DiscriminatedInputWithAllowExtraKeys
{

}
