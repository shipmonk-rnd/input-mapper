<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\MapperFactory\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Array\MapList;
use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapInt;
use ShipMonk\InputMapper\Compiler\Mapper\Wrapper\ValidatedMapperCompiler;
use ShipMonk\InputMapper\Compiler\Validator\Int\AssertPositiveInt;
use ShipMonk\InputMapper\Compiler\Validator\String\AssertStringLength;
use ShipMonk\InputMapper\Compiler\Validator\String\AssertUrl;
use ShipMonk\InputMapper\Runtime\Optional;

class CarInputWithVarTags
{

    public function __construct(
        public readonly int $id,

        #[AssertStringLength(exact: 7)]
        public readonly string $name,

        /** @var Optional<BrandInput> */
        public readonly Optional $brand,

        /** @var array<int> */
        #[MapList(new ValidatedMapperCompiler(new MapInt(), [new AssertPositiveInt()]))]
        public readonly array $numbers,

        #[AssertUrl]
        public readonly ?string $url,
    )
    {
    }

}
