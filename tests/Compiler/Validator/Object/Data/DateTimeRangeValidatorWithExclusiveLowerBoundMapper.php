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
class DateTimeRangeValidatorWithExclusiveLowerBoundMapper implements Mapper
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

        $mapped = DateTimeImmutable::createFromFormat('!Y-m-d', $data);

        if ($mapped === false) {
            throw MappingFailedException::incorrectValue($data, $path, 'date string in Y-m-d format');
        }

        if ($mapped instanceof DateTimeInterface) {
            if ($mapped <= new DateTimeImmutable('2000-01-05')) {
                throw MappingFailedException::incorrectValue($mapped, $path, 'value greater than 2000-01-05');
            }
        }

        return $mapped;
    }
}
