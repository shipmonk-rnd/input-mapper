<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\MapperFactory\Data;

class CarInput
{

    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly BrandInput $brand,
    )
    {
    }

}
