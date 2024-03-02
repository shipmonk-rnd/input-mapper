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
 * @implements Mapper<int<0, max>>
 */
class NonNegativeIntValidatorMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @return int<0, max>
     * @throws MappingFailedException
     */
    public function map(mixed $data, ?MapperContext $context = null): int
    {
        if (!is_int($data)) {
            throw MappingFailedException::incorrectType($data, $context, 'int');
        }

        if ($data < 0) {
            throw MappingFailedException::incorrectValue($data, $context, 'value greater than or equal to 0');
        }

        return $data;
    }
}
