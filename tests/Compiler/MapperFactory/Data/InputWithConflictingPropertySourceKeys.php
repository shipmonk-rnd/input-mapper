<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\MapperFactory\Data;

use ShipMonk\InputMapper\Compiler\Attribute\SourceKey;

class InputWithConflictingPropertySourceKeys
{

    #[SourceKey('value')]
    public readonly int $firstValue;

    #[SourceKey('value')]
    public readonly int $secondValue;

    public function __construct(
        int $firstValue,
        int $secondValue,
    )
    {
        $this->firstValue = $firstValue;
        $this->secondValue = $secondValue;
    }

}
