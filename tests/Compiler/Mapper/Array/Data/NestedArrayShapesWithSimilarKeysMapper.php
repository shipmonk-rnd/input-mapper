<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Array\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Input\ArrayShapeInputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use function array_diff_key;
use function array_key_exists;
use function array_keys;
use function count;
use function is_array;
use function is_int;

/**
 * Generated mapper by {@see ArrayShapeInputMapperCompiler}. Do not edit directly.
 *
 * @implements Mapper<mixed, array{items: array{items: int}, 'a-b': array{a_b: int}, aB: array{ab: int}, größe: array{grüße: int}}>
 */
class NestedArrayShapesWithSimilarKeysMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @param  list<string|int> $path
     * @return array{items: array{items: int}, 'a-b': array{a_b: int}, aB: array{ab: int}, größe: array{grüße: int}}
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): array
    {
        if (!is_array($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'array');
        }

        $mapped = [];

        if (!array_key_exists('items', $data)) {
            throw MappingFailedException::missingKey($path, 'items');
        }

        $mapped['items'] = $this->mapItems($data['items'], [...$path, 'items']);

        if (!array_key_exists('a-b', $data)) {
            throw MappingFailedException::missingKey($path, 'a-b');
        }

        $mapped['a-b'] = $this->mapA_b($data['a-b'], [...$path, 'a-b']);

        if (!array_key_exists('aB', $data)) {
            throw MappingFailedException::missingKey($path, 'aB');
        }

        $mapped['aB'] = $this->mapAB($data['aB'], [...$path, 'aB']);

        if (!array_key_exists('größe', $data)) {
            throw MappingFailedException::missingKey($path, 'größe');
        }

        $mapped['größe'] = $this->mapGröße($data['größe'], [...$path, 'größe']);
        $knownKeys = ['items' => true, 'a-b' => true, 'aB' => true, 'größe' => true];
        $extraKeys = array_diff_key($data, $knownKeys);

        if (count($extraKeys) > 0) {
            throw MappingFailedException::extraKeys($path, array_keys($extraKeys));
        }

        return $mapped;
    }

    /**
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    private function mapItems2(mixed $data, array $path = []): int
    {
        if (!is_int($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'int');
        }

        return $data;
    }

    /**
     * @param  list<string|int> $path
     * @return array{items: int}
     * @throws MappingFailedException
     */
    private function mapItems(mixed $data, array $path = []): array
    {
        if (!is_array($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'array');
        }

        $mapped = [];

        if (!array_key_exists('items', $data)) {
            throw MappingFailedException::missingKey($path, 'items');
        }

        $mapped['items'] = $this->mapItems2($data['items'], [...$path, 'items']);
        $knownKeys = ['items' => true];
        $extraKeys = array_diff_key($data, $knownKeys);

        if (count($extraKeys) > 0) {
            throw MappingFailedException::extraKeys($path, array_keys($extraKeys));
        }

        return $mapped;
    }

    /**
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    private function mapA_b2(mixed $data, array $path = []): int
    {
        if (!is_int($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'int');
        }

        return $data;
    }

    /**
     * @param  list<string|int> $path
     * @return array{a_b: int}
     * @throws MappingFailedException
     */
    private function mapA_b(mixed $data, array $path = []): array
    {
        if (!is_array($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'array');
        }

        $mapped = [];

        if (!array_key_exists('a_b', $data)) {
            throw MappingFailedException::missingKey($path, 'a_b');
        }

        $mapped['a_b'] = $this->mapA_b2($data['a_b'], [...$path, 'a_b']);
        $knownKeys = ['a_b' => true];
        $extraKeys = array_diff_key($data, $knownKeys);

        if (count($extraKeys) > 0) {
            throw MappingFailedException::extraKeys($path, array_keys($extraKeys));
        }

        return $mapped;
    }

    /**
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    private function mapAb2(mixed $data, array $path = []): int
    {
        if (!is_int($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'int');
        }

        return $data;
    }

    /**
     * @param  list<string|int> $path
     * @return array{ab: int}
     * @throws MappingFailedException
     */
    private function mapAB(mixed $data, array $path = []): array
    {
        if (!is_array($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'array');
        }

        $mapped = [];

        if (!array_key_exists('ab', $data)) {
            throw MappingFailedException::missingKey($path, 'ab');
        }

        $mapped['ab'] = $this->mapAb2($data['ab'], [...$path, 'ab']);
        $knownKeys = ['ab' => true];
        $extraKeys = array_diff_key($data, $knownKeys);

        if (count($extraKeys) > 0) {
            throw MappingFailedException::extraKeys($path, array_keys($extraKeys));
        }

        return $mapped;
    }

    /**
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    private function mapGrüße(mixed $data, array $path = []): int
    {
        if (!is_int($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'int');
        }

        return $data;
    }

    /**
     * @param  list<string|int> $path
     * @return array{grüße: int}
     * @throws MappingFailedException
     */
    private function mapGröße(mixed $data, array $path = []): array
    {
        if (!is_array($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'array');
        }

        $mapped = [];

        if (!array_key_exists('grüße', $data)) {
            throw MappingFailedException::missingKey($path, 'grüße');
        }

        $mapped['grüße'] = $this->mapGrüße($data['grüße'], [...$path, 'grüße']);
        $knownKeys = ['grüße' => true];
        $extraKeys = array_diff_key($data, $knownKeys);

        if (count($extraKeys) > 0) {
            throw MappingFailedException::extraKeys($path, array_keys($extraKeys));
        }

        return $mapped;
    }
}
