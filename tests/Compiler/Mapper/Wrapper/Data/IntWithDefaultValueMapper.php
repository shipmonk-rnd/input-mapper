<?php declare (strict_types=1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Wrapper\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Wrapper\MapDefaultValue;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use function is_int;

/**
 * Generated mapper by {@see MapDefaultValue}. Do not edit directly.
 *
 * @implements Mapper<int>
 */
class IntWithDefaultValueMapper implements Mapper
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

        return $data;
    }
}
