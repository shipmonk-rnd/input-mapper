<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\MapperFactory\Data;

use ShipMonk\InputMapper\Compiler\Attribute\SourceKey;

class DiscriminatedInputWithRenamedKeySubtype extends DiscriminatedInputWithRenamedKey
{

    public function __construct(
        #[SourceKey('type')]
        public readonly string $kind,
    )
    {
    }

}
