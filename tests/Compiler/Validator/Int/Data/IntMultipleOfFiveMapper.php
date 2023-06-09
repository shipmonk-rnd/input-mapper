<?php declare (strict_types=1);

namespace ShipMonkTests\InputMapper\Compiler\Validator\Int\Data;

use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use function is_int;

/**
 * Generated mapper. Do not edit directly.
 *
 * @implements Mapper<mixed>
 */
class IntMultipleOfFiveMapper implements Mapper
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
        if (is_int($data) && $data % 5 !== 0) {
            throw MappingFailedException::incorrectValue($data, $path, 'multiple of 5');
        }

        return $data;
    }
}
