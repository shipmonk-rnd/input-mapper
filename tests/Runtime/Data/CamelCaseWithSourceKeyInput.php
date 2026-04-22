<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Runtime\Data;

use ShipMonk\InputMapper\Compiler\Attribute\SourceKey;

class CamelCaseWithSourceKeyInput
{

    public function __construct(
        public readonly int $userId,
        #[SourceKey('CustomKey')]
        public readonly string $firstName,
    )
    {
    }

}
