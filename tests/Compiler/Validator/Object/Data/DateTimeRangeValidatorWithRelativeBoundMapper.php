<?php declare (strict_types=1);

namespace ShipMonkTests\InputMapper\Compiler\Validator\Object\Data;

use DateTimeImmutable;
use DateTimeInterface;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;

/**
 * Generated mapper. Do not edit directly.
 *
 * @implements Mapper<mixed>
 */
class DateTimeRangeValidatorWithRelativeBoundMapper implements Mapper
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
            if ($data < new DateTimeImmutable('now')) {
                throw MappingFailedException::incorrectValue($data, $path, 'value greater than or equal to now');
            }
        }

        return $data;
    }
}
