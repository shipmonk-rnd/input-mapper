<?php declare (strict_types=1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Object\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Object\MapObject;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use function array_diff_key;
use function array_is_list;
use function array_key_exists;
use function array_keys;
use function count;
use function is_array;
use function is_int;

/**
 * Generated mapper by {@see MapObject}. Do not edit directly.
 *
 * @template T
 * @implements Mapper<CollectionInput<T>>
 */
class DelegateToIntCollection__CollectionInputMapper implements Mapper
{
    /**
     * @param array{Mapper<T>} $innerMappers
     */
    public function __construct(private readonly MapperProvider $provider, private readonly array $innerMappers)
    {
    }

    /**
     * @param  list<string|int> $path
     * @return CollectionInput<T>
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): CollectionInput
    {
        if (!is_array($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'array');
        }

        if (!array_key_exists('items', $data)) {
            throw MappingFailedException::missingKey($path, 'items');
        }

        if (!array_key_exists('size', $data)) {
            throw MappingFailedException::missingKey($path, 'size');
        }

        $knownKeys = ['items' => true, 'size' => true];
        $extraKeys = array_diff_key($data, $knownKeys);

        if (count($extraKeys) > 0) {
            throw MappingFailedException::extraKeys($path, array_keys($extraKeys));
        }

        return new CollectionInput(
            $this->mapItems($data['items'], [...$path, 'items']),
            $this->mapSize($data['size'], [...$path, 'size']),
        );
    }

    /**
     * @param  list<string|int> $path
     * @return list<T>
     * @throws MappingFailedException
     */
    private function mapItems(mixed $data, array $path = []): array
    {
        if (!is_array($data) || !array_is_list($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'list');
        }

        $mapped = [];

        foreach ($data as $index => $item) {
            $mapped[] = $this->innerMappers[0]->map($item, [...$path, $index]);
        }

        return $mapped;
    }

    /**
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    private function mapSize(mixed $data, array $path = []): int
    {
        if (!is_int($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'int');
        }

        return $data;
    }
}
