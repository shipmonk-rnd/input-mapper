<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper\Object;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Discriminator
{

    /**
     * @param array<string, class-string> $mapping
     */
    public function __construct(
        public readonly string $key,
        public readonly array $mapping
    )
    {
    }

}
