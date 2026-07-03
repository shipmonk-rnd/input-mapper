<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Codec\Data;

use ShipMonk\InputMapper\Runtime\Codec;

/**
 * Codec base class with a private promoted constructor property.
 *
 * @implements Codec<string, string, string, string>
 */
abstract class BasePrivatePropCodec implements Codec
{

    public function __construct(
        private readonly string $secret,
    )
    {
    }

    /**
     * @param list<string|int> $path
     */
    public function decode(mixed $data, array $path = []): string
    {
        return $this->secret . $data;
    }

    /**
     * @param list<string|int> $path
     */
    public function encode(mixed $data, array $path = []): string
    {
        return $data;
    }

}
