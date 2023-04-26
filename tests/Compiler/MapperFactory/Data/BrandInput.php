<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\MapperFactory\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Object\AllowExtraKeys;

#[AllowExtraKeys]
class BrandInput
{

    public function __construct(
        public readonly string $name,
    )
    {
    }

}
