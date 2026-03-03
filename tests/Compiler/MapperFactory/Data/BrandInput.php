<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\MapperFactory\Data;

use ShipMonk\InputMapper\Compiler\Attribute\AllowExtraKeys;

#[AllowExtraKeys]
class BrandInput
{

    /**
     * @param int<1900, 2100> $foundedIn
     * @param non-empty-list<string> $founders
     */
    public function __construct(
        public readonly string $name,
        public readonly int $foundedIn,
        public readonly array $founders,
    )
    {
    }

}
