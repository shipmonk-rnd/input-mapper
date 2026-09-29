<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\MapperFactory\Data;

use ShipMonk\InputMapper\Compiler\Attribute\Discriminator;

/**
 * The mapping names a class that does not exist. The delegate reports it later, with a message about the class itself.
 */
#[Discriminator('type', ['dog' => 'ShipMonk\InputMapperTests\Compiler\MapperFactory\Data\ThereIsNoSuchSubtype'])] // @phpstan-ignore argument.type
abstract class DiscriminatedInputWithMissingSubtype
{

}
