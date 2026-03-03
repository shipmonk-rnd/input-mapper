<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\MapperFactory\Data;

use ShipMonk\InputMapper\Compiler\Attribute\Optional;
use ShipMonk\InputMapper\Compiler\Validator\Int\AssertInt32;
use ShipMonk\InputMapper\Compiler\Validator\String\AssertStringLength;

class BrandInputWithDefaultValues
{

    /**
     * @param list<string> $founders
     */
    public function __construct(
        #[Optional(default: 'ShipMonk')]
        #[AssertStringLength(min: 5)]
        public readonly string $name,

        #[Optional]
        #[AssertInt32]
        public readonly ?int $foundedIn,

        #[Optional(default: ['Jan Bednář'])]
        public readonly array $founders,
    )
    {
    }

}
