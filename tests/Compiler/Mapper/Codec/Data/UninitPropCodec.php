<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Codec\Data;

use ShipMonk\InputMapper\Runtime\Codec;

/**
 * Codec whose constructor parameter has a matching property that is never assigned,
 * used to test inline compilation limits.
 *
 * @implements Codec<string, string, string, string>
 */
class UninitPropCodec implements Codec
{

    private string $mode;

    public function __construct(
        string $mode,
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
