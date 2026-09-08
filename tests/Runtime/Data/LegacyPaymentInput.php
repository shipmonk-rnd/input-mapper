<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Runtime\Data;

use ShipMonk\InputMapper\Compiler\Attribute\MapCodec;
use ShipMonk\InputMapper\Compiler\Attribute\MapNullable;
use ShipMonk\InputMapperTests\Compiler\Mapper\Codec\Data\CurrencyPrefixCodec;

class LegacyPaymentInput
{

    public function __construct(
        #[CurrencyPrefixCodec(prefix: 'USD')]
        public readonly string $amount,
        #[MapNullable(new MapCodec(new CurrencyPrefixCodec(prefix: 'EUR', separator: '-')))]
        public readonly ?string $refund,
    )
    {
    }

}
