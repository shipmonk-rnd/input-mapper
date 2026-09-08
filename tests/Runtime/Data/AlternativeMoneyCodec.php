<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Runtime\Data;

use ShipMonk\InputMapper\Runtime\Codec;

/**
 * Second codec claiming MoneyValue, used to test conflicting codec registrations.
 *
 * @implements Codec<string, MoneyValue, MoneyValue, string>
 */
class AlternativeMoneyCodec implements Codec
{

    /**
     * @param list<string|int> $path
     */
    public function decode(mixed $data, array $path = []): MoneyValue
    {
        [$currency, $cents] = explode(' ', $data);
        return new MoneyValue($currency, (int) $cents);
    }

    /**
     * @param list<string|int> $path
     */
    public function encode(mixed $data, array $path = []): string
    {
        return "{$data->currency} {$data->cents}";
    }

}
