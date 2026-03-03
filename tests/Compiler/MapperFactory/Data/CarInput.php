<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\MapperFactory\Data;

use ShipMonk\InputMapper\Compiler\Attribute\MapList;
use ShipMonk\InputMapper\Compiler\Mapper\Input\IntInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Input\ValidatedInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Validator\Int\AssertPositiveInt;
use ShipMonk\InputMapper\Compiler\Validator\String\AssertStringLength;
use ShipMonk\InputMapper\Compiler\Validator\String\AssertUrl;
use ShipMonk\InputMapper\Runtime\Optional;

class CarInput
{

    /**
     * @param  Optional<BrandInput> $brand
     * @param  array<int>           $numbers
     */
    public function __construct(
        public readonly int $id,
        #[AssertStringLength(exact: 7)]
        public readonly string $name,
        public readonly Optional $brand,
        #[MapList(new ValidatedInputMapperCompiler(new IntInputMapperCompiler(), [new AssertPositiveInt()]))]
        public readonly array $numbers,
        #[AssertUrl]
        public readonly ?string $url,
    )
    {
    }

}
