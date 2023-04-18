<?php declare (strict_types=1);

namespace ShipMonkTests\InputMapper\Compiler\Validator\String\Data;

use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use ShipMonk\InputMapper\Runtime\MappingFailedException;
use function is_string;
use function strlen;

/**
 * Generated mapper. Do not edit directly.
 *
 * @implements Mapper<mixed>
 */
class StringLengthValidatorWithExactMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @param  list<string|int> $path
     */
    public function map(mixed $data, array $path = []): mixed
    {
        if (is_string($data)) {
            if (strlen($data) !== 5) {
                throw MappingFailedException::incorrectValue($data, $path, 'string with exactly 5 characters');
            }
        }

        return $data;
    }
}
