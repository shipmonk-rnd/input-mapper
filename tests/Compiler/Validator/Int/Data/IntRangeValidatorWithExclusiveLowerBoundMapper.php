<?php declare (strict_types=1);

namespace ShipMonkTests\InputMapper\Compiler\Validator\Int\Data;

use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use ShipMonk\InputMapper\Runtime\MappingFailedException;
use function is_int;

/**
 * Generated mapper. Do not edit directly.
 *
 * @implements Mapper<mixed>
 */
class IntRangeValidatorWithExclusiveLowerBoundMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @param  list<string|int> $path
     */
    public function map(mixed $data, array $path = []): mixed
    {
        if (is_int($data)) {
            if ($data <= 5) {
                throw MappingFailedException::incorrectValue($data, $path, 'value greater than 5');
            }
        }

        return $data;
    }
}
