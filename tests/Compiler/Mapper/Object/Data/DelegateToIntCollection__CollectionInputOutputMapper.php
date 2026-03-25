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
class DelegateToIntCollection__CollectionInputOutputMapper implements OutputMapper
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
     * @return array{items: mixed, size: int}
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): mixed
    {
        return ['items' => $data->items, 'size' => $data->size];
    }
}
