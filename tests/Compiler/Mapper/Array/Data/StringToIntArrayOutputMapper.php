<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Array\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Output\ArrayOutputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\OutputMapper;
use ShipMonk\InputMapper\Runtime\OutputMapperProvider;

/**
 * Generated mapper by {@see ArrayOutputMapperCompiler}. Do not edit directly.
 *
 * @implements OutputMapper<array<string, int>>
 */
class StringToIntArrayOutputMapper implements OutputMapper
{
    public function __construct(private readonly OutputMapperProvider $provider)
    {
    }

    /**
     * @param  array<string, int> $data
     * @param  list<string|int> $path
     * @return array<string, int>
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): mixed
    {
        $mapped = [];

        foreach ($data as $key => $value) {
            $mapped[$key] = $value;
        }

        return $mapped;
    }
}
