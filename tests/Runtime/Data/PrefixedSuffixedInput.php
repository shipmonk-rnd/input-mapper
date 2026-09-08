<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Runtime\Data;

use ShipMonk\InputMapperTests\Compiler\Mapper\Codec\Data\CurrencyPrefixCodec;
use ShipMonk\InputMapperTests\Compiler\Mapper\Codec\Data\SuffixCodec;

class PrefixedSuffixedInput
{

    public function __construct(
        #[CurrencyPrefixCodec(prefix: 'USD')]
        #[SuffixCodec(suffix: '!')]
        public readonly string $amount,
    )
    {
    }

}
