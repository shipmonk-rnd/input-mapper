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
class IntRangeValidatorWithInclusiveLowerAndUpperBoundMapper implements Mapper
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
        if (is_int($data)) {
            if ($data < 5) {
                throw MappingFailedException::incorrectValue($data, $path, 'value greater than or equal to 5');
            }

            if ($data > 10) {
                throw MappingFailedException::incorrectValue($data, $path, 'value less than or equal to 10');
            }
        }

        return $data;
    }
}
