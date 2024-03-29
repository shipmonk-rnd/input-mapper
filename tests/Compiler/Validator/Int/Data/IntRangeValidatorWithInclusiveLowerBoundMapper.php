<?php declare (strict_types=1);

namespace ShipMonkTests\InputMapper\Compiler\Validator\Int\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Wrapper\ValidatedMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use function is_int;

/**
 * Generated mapper by {@see ValidatedMapperCompiler}. Do not edit directly.
 *
 * @implements Mapper<int<5, max>>
 */
class IntRangeValidatorWithInclusiveLowerBoundMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @param  list<string|int> $path
     * @return int<5, max>
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): int
    {
        if (!is_int($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'int');
        }

        if ($data < 5) {
            throw MappingFailedException::incorrectValue($data, $path, 'value greater than or equal to 5');
        }

        return $data;
    }
}
