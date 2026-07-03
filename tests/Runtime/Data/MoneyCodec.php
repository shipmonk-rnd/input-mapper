<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Runtime\Data;

use ShipMonk\InputMapper\Runtime\Codec;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;

/**
 * @implements Codec<array{currency: string, cents: int}, MoneyValue, MoneyValue, array{currency: string, cents: int}>
 */
class MoneyCodec implements Codec
{

    /**
     * @param list<string|int> $path
     * @throws MappingFailedException
     */
    public function decode(mixed $data, array $path = []): MoneyValue
    {
        if ($data['cents'] < 0) {
            throw MappingFailedException::incorrectValue($data['cents'], [...$path, 'cents'], 'non-negative integer');
        }

        return new MoneyValue($data['currency'], $data['cents']);
    }

    /**
     * @param list<string|int> $path
     */
    public function encode(mixed $data, array $path = []): array
    {
        return [
            'currency' => $data->currency,
            'cents' => $data->cents,
        ];
    }

}
