<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Output\ObjectOutputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;

/**
 * Generated mapper by {@see ObjectOutputMapperCompiler}. Do not edit directly.
 *
 * @template T
 * @implements Mapper<CollectionInput<T>, mixed>
 */
class DelegateToEnumCollection__CollectionInputOutputMapper implements Mapper
{
    /**
     * @param array{Mapper<T, mixed>} $innerMappers
     */
    public function __construct(private readonly MapperProvider $provider, private readonly array $innerMappers)
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
