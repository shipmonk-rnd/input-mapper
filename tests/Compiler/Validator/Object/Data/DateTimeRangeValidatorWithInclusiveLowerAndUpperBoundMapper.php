<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Validator\Object\Data;

use DateTimeImmutable;
use ShipMonk\InputMapper\Compiler\Mapper\Input\ValidatedInputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\InputMapper;
use ShipMonk\InputMapper\Runtime\InputMapperProvider;
use function is_string;

/**
 * Generated mapper by {@see ValidatedInputMapperCompiler}. Do not edit directly.
 *
 * @implements InputMapper<DateTimeImmutable>
 */
class DateTimeRangeValidatorWithInclusiveLowerAndUpperBoundMapper implements InputMapper
{
    public function __construct(private readonly InputMapperProvider $provider)
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

        if ($mapped < new DateTimeImmutable('2000-01-05')) {
            throw MappingFailedException::incorrectValue($mapped, $path, 'value greater than or equal to 2000-01-05');
        }

        if ($mapped > new DateTimeImmutable('2000-01-10')) {
            throw MappingFailedException::incorrectValue($mapped, $path, 'value less than or equal to 2000-01-10');
        }

        return $mapped;
    }
}
