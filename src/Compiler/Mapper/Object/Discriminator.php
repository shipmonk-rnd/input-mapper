<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper\Object;

use Attribute;

/**
 * Provides a way to map a polymorphic classes with common base class, according to the discriminator key.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Discriminator
{

    /**
     * @param array<string, class-string> $mapping Mapping of discriminator values to class names
     */
    public function __construct(
        public readonly string $key,
        public readonly array $mapping
    )
    {
    }

}
