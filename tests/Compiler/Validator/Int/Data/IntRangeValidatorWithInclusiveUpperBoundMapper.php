<?php declare (strict_types=1);

namespace ShipMonkTests\InputMapper\Compiler\Validator\Int\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Wrapper\ValidatedMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperContext;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use function is_int;

/**
 * Generated mapper by {@see ValidatedMapperCompiler}. Do not edit directly.
 *
 * @implements Mapper<int<min, 5>>
 */
class IntRangeValidatorWithInclusiveUpperBoundMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @return int<min, 5>
     * @throws MappingFailedException
     */
    public function map(mixed $data, ?MapperContext $context = null): int
    {
        if (!is_int($data)) {
            throw MappingFailedException::incorrectType($data, $context, 'int');
        }

        if ($data > 5) {
            throw MappingFailedException::incorrectValue($data, $context, 'value less than or equal to 5');
        }

        return $data;
    }
}
