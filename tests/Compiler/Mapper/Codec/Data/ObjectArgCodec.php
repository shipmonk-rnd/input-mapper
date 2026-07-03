<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Codec\Data;

use ShipMonk\InputMapper\Runtime\Codec;
use ShipMonk\InputMapperTests\Runtime\Data\MoneyValue;

/**
 * Codec with a non-scalar constructor argument, used to test inline compilation limits.
 *
 * @implements Codec<string, string, string, string>
 */
class ObjectArgCodec implements Codec
{

    public function __construct(
        public readonly MoneyValue $money,
    )
    {
    }

    /**
     * @param list<string|int> $path
     */
    public function decode(mixed $data, array $path = []): string
    {
        return $data;
    }

    /**
     * @param list<string|int> $path
     */
    public function encode(mixed $data, array $path = []): string
    {
        return $data;
    }

}
