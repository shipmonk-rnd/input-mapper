<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Output\ObjectOutputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Optional;
use ShipMonk\InputMapper\Runtime\OutputMapper;
use ShipMonk\InputMapper\Runtime\OutputMapperProvider;

/**
 * Generated mapper by {@see ObjectOutputMapperCompiler}. Do not edit directly.
 *
 * @implements OutputMapper<HierarchicalChildOneInput>
 */
class HierarchicalParent__HierarchicalChildOneInputOutputMapper implements OutputMapper
{
    public function __construct(private readonly OutputMapperProvider $provider)
    {
    }

    /**
     * @param  HierarchicalChildOneInput $data
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): mixed
    {
        $output = ['id' => $this->mapId($data->id, [...$path, 'id']), 'name' => $this->mapName($data->name, [...$path, 'name']), 'type' => $this->mapType($data->type, [...$path, 'type']), 'childOneField' => $this->mapChildOneField($data->childOneField, [...$path, 'childOneField'])];

        if ($data->age->isDefined()) {
            $output['age'] = $this->mapAge($data->age, [...$path, 'age']);
        }

        return $output;
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

    /**
     * @param  Optional<int> $data
     * @param  list<string|int> $path
     * @return int
     * @throws MappingFailedException
     */
    private function mapAge(mixed $data, array $path = []): mixed
    {
        return $data->get();
    }

    /**
     * @param  string $data
     * @param  list<string|int> $path
     * @return string
     * @throws MappingFailedException
     */
    private function mapType(mixed $data, array $path = []): mixed
    {
        return $data;
    }

    /**
     * @param  string $data
     * @param  list<string|int> $path
     * @return string
     * @throws MappingFailedException
     */
    private function mapChildOneField(mixed $data, array $path = []): mixed
    {
        return $data;
    }
}
