<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\MapperFactory\Data;

class BrandInput
{

    public function __construct(
        public readonly string $name,
    )
    {
    }

}
