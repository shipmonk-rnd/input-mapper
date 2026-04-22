<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\MapperFactory\Data;

class InputWithTransformerCollidingKeys
{

    public function __construct(
        public readonly int $fooBar,
        public readonly int $foo_bar,
    )
    {
    }

}
