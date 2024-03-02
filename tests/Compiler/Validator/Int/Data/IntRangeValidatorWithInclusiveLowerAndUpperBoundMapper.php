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
 * @implements Mapper<int<5, 10>>
 */
class IntRangeValidatorWithInclusiveLowerAndUpperBoundMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @return int<5, 10>
     * @throws MappingFailedException
     */
    public function map(mixed $data, ?MapperContext $context = null): int
    {
        if (!is_int($data)) {
            throw MappingFailedException::incorrectType($data, $context, 'int');
        }

        if ($data < 5) {
            throw MappingFailedException::incorrectValue($data, $context, 'value greater than or equal to 5');
        }

        if ($data > 10) {
            throw MappingFailedException::incorrectValue($data, $context, 'value less than or equal to 10');
        }

        return $data;
    }
}
