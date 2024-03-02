<?php declare (strict_types=1);

namespace ShipMonkTests\InputMapper\Compiler\Validator\Object\Data;

use DateTimeImmutable;
use ShipMonk\InputMapper\Compiler\Mapper\Wrapper\ValidatedMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperContext;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use function is_string;

/**
 * Generated mapper by {@see ValidatedMapperCompiler}. Do not edit directly.
 *
 * @implements Mapper<DateTimeImmutable>
 */
class DateTimeRangeValidatorWithInclusiveLowerAndUpperBoundMapper implements Mapper
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

        $mapped = DateTimeImmutable::createFromFormat('!Y-m-d', $data);

        if ($mapped === false) {
            throw MappingFailedException::incorrectValue($data, $context, 'date string in Y-m-d format');
        }

        if ($mapped < new DateTimeImmutable('2000-01-05')) {
            throw MappingFailedException::incorrectValue($mapped, $context, 'value greater than or equal to 2000-01-05');
        }

        if ($mapped > new DateTimeImmutable('2000-01-10')) {
            throw MappingFailedException::incorrectValue($mapped, $context, 'value less than or equal to 2000-01-10');
        }

        return $mapped;
    }
}
