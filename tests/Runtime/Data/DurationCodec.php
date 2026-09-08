<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Runtime\Data;

use ShipMonk\InputMapper\Runtime\Codec;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;

/**
 * Asymmetric codec: decodes into the concrete FixedDuration, but encodes any Duration implementation.
 *
 * @implements Codec<int, Duration, FixedDuration, int>
 */
class DurationCodec implements Codec
{

    /**
     * @param list<string|int> $path
     * @throws MappingFailedException
     */
    public function decode(mixed $data, array $path = []): FixedDuration
    {
        if ($data < 0) {
            throw MappingFailedException::incorrectValue($data, $path, 'non-negative duration in seconds');
        }

        return new FixedDuration($data);
    }

    /**
     * @param list<string|int> $path
     */
    public function encode(mixed $data, array $path = []): int
    {
        return $data->toSeconds();
    }

}
