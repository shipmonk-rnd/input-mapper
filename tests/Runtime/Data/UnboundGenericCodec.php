<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Runtime\Data;

use ShipMonk\InputMapper\Runtime\Codec;

/**
 * Codec without any inferable domain class (unbound template parameter), must be rejected on registration.
 *
 * @template T
 * @implements Codec<string, T, T, string>
 */
class UnboundGenericCodec implements Codec
{

    /**
     * @param string $data
     * @param list<string|int> $path
     * @return T
     */
    public function decode(mixed $data, array $path = []): mixed
    {
        return $data; // @phpstan-ignore return.type
    }

    /**
     * @param T $data
     * @param list<string|int> $path
     */
    public function encode(mixed $data, array $path = []): string
    {
        return (string) $data; // @phpstan-ignore cast.string
    }

}
