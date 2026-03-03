<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Output\ObjectOutputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\OutputMapper;
use ShipMonk\InputMapper\Runtime\OutputMapperProvider;

/**
 * Generated mapper by {@see ObjectOutputMapperCompiler}. Do not edit directly.
 *
 * @implements OutputMapper<SimplePersonInput>
 */
class SimplePersonOutputMapper implements OutputMapper
{
    public function __construct(private readonly OutputMapperProvider $provider)
    {
    }

    /**
     * @param  SimplePersonInput $data
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): mixed
    {
        return ['id' => $this->mapId($data->id, [...$path, 'id']), 'name' => $this->mapName($data->name, [...$path, 'name'])];
    }

    /**
     * @param  int $data
     * @param  list<string|int> $path
     * @return int
     * @throws MappingFailedException
     */
    private function mapId(mixed $data, array $path = []): mixed
    {
        return $data;
    }

    /**
     * @param  string $data
     * @param  list<string|int> $path
     * @return string
     * @throws MappingFailedException
     */
    private function mapName(mixed $data, array $path = []): mixed
    {
        return $data;
    }
}
