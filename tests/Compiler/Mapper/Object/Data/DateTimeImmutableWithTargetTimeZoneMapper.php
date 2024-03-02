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
class DateTimeImmutableWithTargetTimeZoneMapper implements Mapper
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

        $mapped = DateTimeImmutable::createFromFormat('Y-m-d\\TH:i:sP', $data);

        if ($mapped === false) {
            $mapped = DateTimeImmutable::createFromFormat('Y-m-d\\TH:i:s.vP', $data);
        }

        if ($mapped === false) {
            throw MappingFailedException::incorrectValue($data, $context, 'date-time string in RFC 3339 format');
        }

        $mapped = $mapped->setTimezone(new DateTimeZone('Europe/Prague'));
        return $mapped;
    }
}
