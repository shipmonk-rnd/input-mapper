<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Array\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Output\ArrayOutputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;

/**
 * Generated mapper by {@see ArrayOutputMapperCompiler}. Do not edit directly.
 *
 * @implements Mapper<array<string, int>, array<string, int>>
 */
class StringToIntArrayOutputMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @param  array<string, int> $data
     * @param  list<string|int> $path
     * @return array<string, int>
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): array
    {
        return $data;
    }
}
