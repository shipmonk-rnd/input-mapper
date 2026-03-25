<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Data;

use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;

/**
 * Generated mapper by {@see MapMultiplyBySeven}. Do not edit directly.
 *
 * @implements Mapper<mixed, int>
 */
class MultiplyBySevenMapper implements Mapper
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
        return MapMultiplyBySeven::mapValue($data, $path);
    }
}
