<?php declare (strict_types=1);

namespace ShipMonkTests\InputMapper\Compiler\Validator\Int\Data;

use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use function is_int;

/**
 * Generated mapper. Do not edit directly.
 *
 * @implements Mapper<int>
 */
class IntRangeValidatorWithInclusiveUpperBoundMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): int
    {
        if (!is_int($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'int');
        }

        if (is_int($data)) {
            if ($data > 5) {
                throw MappingFailedException::incorrectValue($data, $path, 'value less than or equal to 5');
            }
        }

        return $data;
    }
}
