<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Data;

use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\InputMapper;
use ShipMonk\InputMapper\Runtime\InputMapperProvider;

/**
 * Generated mapper by {@see MapMultiplyBySeven}. Do not edit directly.
 *
 * @implements InputMapper<int>
 */
class MultiplyBySevenMapper implements InputMapper
{
    public function __construct(private readonly InputMapperProvider $provider)
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
