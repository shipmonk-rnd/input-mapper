<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\MapperFactory\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Array\MapList;
use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapInt;
use ShipMonk\InputMapper\Compiler\Validator\String\AssertStringLength;
use ShipMonk\InputMapper\Runtime\Optional;

class CarInput
{

    /**
     * @param  Optional<BrandInput> $brand
     */
    public function __construct(
        public readonly int $id,
        #[AssertStringLength(exact: 7)]
        public readonly string $name,
        public readonly Optional $brand,
        #[MapList(new MapInt())]
        public readonly array $numbers,
    )
    {
    }

}
