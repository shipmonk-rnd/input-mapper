<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\MapperFactory\Data;

use ShipMonk\InputMapper\Compiler\Attribute\Discriminator;

#[Discriminator('breed', ['dog' => DiscriminatedInputWithNestedDiscriminatorLeaf::class])]
abstract class DiscriminatedInputWithNestedDiscriminatorSubtype extends DiscriminatedInputWithNestedDiscriminator
{

}
