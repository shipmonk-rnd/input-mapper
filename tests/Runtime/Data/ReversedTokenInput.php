<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Runtime\Data;

use ShipMonk\InputMapperTests\Compiler\Mapper\Codec\Data\InputOnlyProviderCodec;

class ReversedTokenInput
{

    public function __construct(
        #[InputOnlyProviderCodec]
        public readonly string $token,
    )
    {
    }

}
