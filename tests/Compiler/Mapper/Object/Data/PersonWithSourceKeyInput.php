<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data;

use ShipMonk\InputMapper\Compiler\Attribute\SourceKey;

class PersonWithSourceKeyInput
{

    public function __construct(
        public readonly int $id,
        #[SourceKey('full_name')]
        public readonly string $name,
    )
    {
    }

}
