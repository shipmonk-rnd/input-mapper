<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Runtime\Data;

use DateTimeImmutable;
use DateTimeInterface;
use ShipMonk\InputMapper\Runtime\Codec;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;

/**
 * @implements Codec<string, DateTimeInterface, DateTimeImmutable, string>
 */
class DateTimeInterfaceCodec implements Codec
{

    /**
     * @param list<string|int> $path
     * @throws MappingFailedException
     */
    public function decode(mixed $data, array $path = []): DateTimeImmutable
    {
        return new DateTimeImmutable($data);
    }

    /**
     * @param list<string|int> $path
     */
    public function encode(mixed $data, array $path = []): string
    {
        return $data->format('c');
    }

}
