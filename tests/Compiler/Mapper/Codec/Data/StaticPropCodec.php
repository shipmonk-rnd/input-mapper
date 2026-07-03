<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Codec\Data;

use ShipMonk\InputMapper\Runtime\Codec;

/**
 * Codec whose constructor parameter is shadowed by a static property, used to test inline compilation limits.
 *
 * @implements Codec<string, string, string, string>
 */
class StaticPropCodec implements Codec
{

    public static string $mode = 'GLOBAL_DEFAULT';

    public function __construct(
        string $mode,
    )
    {
        self::$mode = $mode;
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
