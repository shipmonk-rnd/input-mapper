<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\MapperFactory\Data;

use ShipMonk\InputMapper\Compiler\Attribute\SourceKey;

class InputWithConflictingSourceKeys
{

    public function __construct(
        #[SourceKey('value')]
        public readonly int $firstValue,

        #[SourceKey('value')]
        public readonly int $secondValue,
    )
    {
    }

}
