<?php declare (strict_types=1);

namespace ShipMonkTests\InputMapper\Compiler\Validator\Object\Data;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;

/**
 * Generated mapper. Do not edit directly.
 *
 * @implements Mapper<mixed>
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
    public function map(mixed $data, array $path = []): mixed
    {
        if ($data instanceof DateTimeInterface) {
            $timezone = new DateTimeZone('America/New_York');

            if ($data < new DateTimeImmutable(
                '2000-01-05',
                $timezone,
            )) {
                throw MappingFailedException::incorrectValue($data, $path, 'value greater than or equal to 2000-01-05 (in America/New_York timezone)');
            }
        }

        return $data;
    }
}
