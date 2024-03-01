<?php declare (strict_types=1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Data;

use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperContext;
use ShipMonk\InputMapper\Runtime\MapperProvider;

/**
 * Generated mapper by {@see MapMultiplyBySeven}. Do not edit directly.
 *
 * @implements Mapper<int>
 */
class MultiplyBySevenMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @throws MappingFailedException
     */
    public function map(mixed $data, ?MapperContext $context = null): int
    {
        return MapMultiplyBySeven::mapValue($data, $context);
    }
}
