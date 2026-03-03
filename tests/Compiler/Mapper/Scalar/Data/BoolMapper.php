<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Scalar\Data;

use ShipMonk\InputMapper\Compiler\Attribute\MapBool;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use function is_bool;

/**
 * Generated mapper by {@see MapBool}. Do not edit directly.
 *
 * @implements Mapper<bool>
 */
class BoolMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): bool
    {
        if (!is_bool($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'bool');
        }

        return $data;
    }
}
