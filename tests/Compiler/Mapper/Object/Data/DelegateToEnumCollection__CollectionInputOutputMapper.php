<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Output\ObjectOutputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\OutputMapper;
use ShipMonk\InputMapper\Runtime\OutputMapperProvider;

/**
 * Generated mapper by {@see ObjectOutputMapperCompiler}. Do not edit directly.
 *
 * @template T
 * @implements OutputMapper<CollectionInput<T>>
 */
class DelegateToEnumCollection__CollectionInputOutputMapper implements OutputMapper
{
    /**
     * @param array{OutputMapper<T>} $innerMappers
     */
    public function __construct(private readonly OutputMapperProvider $provider, private readonly array $innerMappers)
    {
    }

    /**
     * @param  CollectionInput<T> $data
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): mixed
    {
        return ['items' => $this->mapItems($data->items, [...$path, 'items']), 'size' => $this->mapSize($data->size, [...$path, 'size'])];
    }

    /**
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    private function mapItems(mixed $data, array $path = []): mixed
    {
        return $data;
    }

    /**
     * @param  int $data
     * @param  list<string|int> $path
     * @return int
     * @throws MappingFailedException
     */
    private function mapSize(mixed $data, array $path = []): mixed
    {
        return $data;
    }
}
