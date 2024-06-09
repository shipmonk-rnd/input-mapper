<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\MapperFactory\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Object\SourceKey;

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
