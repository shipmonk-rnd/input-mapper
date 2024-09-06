<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper\Object;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Discriminator
{

    public function __construct(
        public readonly string $key,
        /**
         * @var array<string, class-string>
         */
        public readonly array $mapping
    )
    {
    }

}
