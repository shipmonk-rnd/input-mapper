<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\MapperFactory\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapString;

class InputWithIncompatibleMapperCompiler
{

    public function __construct(
        #[MapString]
        public readonly int $id,
    )
    {
    }

}
