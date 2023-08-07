<?php declare (strict_types=1);

namespace ShipMonkTests\InputMapper\Compiler\Validator\Object\Data;

use DateTimeImmutable;
use DateTimeZone;
use ShipMonk\InputMapper\Compiler\Mapper\Wrapper\ValidatedMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use function is_string;

/**
 * Generated mapper by {@see ValidatedMapperCompiler}. Do not edit directly.
 *
 * @implements Mapper<DateTimeImmutable>
 */
class DateTimeRangeValidatorWithTimezoneMapper implements Mapper
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

        $timezone = new DateTimeZone('America/New_York');
        $mapped = DateTimeImmutable::createFromFormat('!Y-m-d', $data, $timezone);

        if ($mapped === false) {
            throw MappingFailedException::incorrectValue($data, $path, 'date-time string in RFC 3339 format');
        }

        $timezone2 = new DateTimeZone('America/New_York');

        if ($mapped < new DateTimeImmutable(
            '2000-01-05',
            $timezone2,
        )) {
            throw MappingFailedException::incorrectValue($mapped, $path, 'value greater than or equal to 2000-01-05 (in America/New_York timezone)');
        }

        return $mapped;
    }
}
