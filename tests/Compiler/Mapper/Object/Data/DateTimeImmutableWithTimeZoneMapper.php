<?php declare (strict_types=1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Object\Data;

use DateTimeImmutable;
use DateTimeZone;
use ShipMonk\InputMapper\Compiler\Mapper\Object\MapDateTimeImmutable;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use function is_string;

/**
 * Generated mapper by {@see MapDateTimeImmutable}. Do not edit directly.
 *
 * @implements Mapper<DateTimeImmutable>
 */
class DateTimeImmutableWithTimeZoneMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): DateTimeImmutable
    {
        if (!is_string($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'string');
        }

        $timezone = new DateTimeZone('Europe/Prague');
        $mapped = DateTimeImmutable::createFromFormat('Y-m-d\\TH:i:sP', $data, $timezone);

        if ($mapped === false) {
            $mapped = DateTimeImmutable::createFromFormat('Y-m-d\\TH:i:s.vP', $data, $timezone);
        }

        if ($mapped === false) {
            throw MappingFailedException::incorrectValue($data, $path, 'date-time string in RFC 3339 format');
        }

        $mapped = $mapped->setTimezone($timezone);
        return $mapped;
    }
}
