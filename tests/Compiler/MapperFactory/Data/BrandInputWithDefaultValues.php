<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\MapperFactory\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Optional;

class BrandInputWithDefaultValues
{

    /**
     * @param list<string> $founders
     */
    public function __construct(
        #[Optional(default: 'ShipMonk')]
        public readonly string $name,

        #[Optional]
        public readonly ?int $foundedIn,

        #[Optional(default: ['Jan Bednář'])]
        public readonly array $founders,
    )
    {
    }

}
