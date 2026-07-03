<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Runtime\Data;

use ShipMonk\InputMapper\Runtime\Codec;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use function str_starts_with;

/**
 * @implements Codec<string, HexColor>
 */
class HexColorCodec implements Codec
{

    /**
     * @param list<string|int> $path
     * @throws MappingFailedException
     */
    public function decode(mixed $data, array $path = []): HexColor
    {
        if (!str_starts_with($data, '#')) {
            throw MappingFailedException::incorrectValue($data, $path, 'hex color string');
        }

        return new HexColor($data);
    }

    /**
     * @param list<string|int> $path
     */
    public function encode(mixed $data, array $path = []): string
    {
        return $data->hex;
    }

}
