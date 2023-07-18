<?php declare (strict_types=1);

namespace ShipMonkTests\InputMapper\Compiler\Validator\Object\Data;

use DateTimeImmutable;
use DateTimeInterface;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use function is_string;

/**
 * Generated mapper. Do not edit directly.
 *
 * @implements Mapper<DateTimeImmutable>
 */
class DateTimeRangeValidatorWithBothDateAndTimeMapper implements Mapper
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

        $mapped = DateTimeImmutable::createFromFormat('Y-m-d\\TH:i:sP', $data);

        if ($mapped === false) {
            throw MappingFailedException::incorrectValue($data, $path, 'date-time string in RFC 3339 format');
        }

        if ($mapped instanceof DateTimeInterface) {
            if ($mapped < new DateTimeImmutable('2000-01-01T00:00:05Z')) {
                throw MappingFailedException::incorrectValue($mapped, $path, 'value greater than or equal to 2000-01-01T00:00:05Z');
            }
        }

        return $mapped;
    }
}
