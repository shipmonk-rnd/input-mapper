<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\MapperFactory\Data;

use ShipMonk\InputMapper\Compiler\Attribute\SourceKey;

class InputWithRenamedSourceKey
{

    public function __construct(
        #[SourceKey('old_value')]
        public readonly int $oldValue,

        #[SourceKey('new_value')]
        public readonly int $newValue,
    )
    {
    }

}
