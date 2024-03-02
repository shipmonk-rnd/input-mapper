<?php declare (strict_types=1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Object\Data;

use DateTimeImmutable;
use DateTimeZone;
use ShipMonk\InputMapper\Compiler\Mapper\Object\MapDateTimeImmutable;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperContext;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use function is_string;

/**
 * Generated mapper by {@see MapDateTimeImmutable}. Do not edit directly.
 *
 * @implements Mapper<DateTimeImmutable>
 */
class DateWithDistinctDefaultAndTargetTimeZoneMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @throws MappingFailedException
     */
    public function map(mixed $data, ?MapperContext $context = null): DateTimeImmutable
    {
        if (!is_string($data)) {
            throw MappingFailedException::incorrectType($data, $context, 'string');
        }

        $timezone = new DateTimeZone('Europe/Prague');
        $mapped = DateTimeImmutable::createFromFormat('!Y-m-d', $data, $timezone);

        if ($mapped === false) {
            throw MappingFailedException::incorrectValue($data, $context, 'date string in Y-m-d format');
        }

        $mapped = $mapped->setTimezone(new DateTimeZone('America/New_York'));
        return $mapped;
    }
}
