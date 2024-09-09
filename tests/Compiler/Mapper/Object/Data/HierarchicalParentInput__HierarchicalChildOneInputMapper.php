<?php declare (strict_types=1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Object\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Object\MapObject;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use ShipMonk\InputMapper\Runtime\Optional;
use ShipMonk\InputMapper\Runtime\OptionalSome;
use function array_diff_key;
use function array_key_exists;
use function array_keys;
use function count;
use function is_array;
use function is_int;
use function is_string;

/**
 * Generated mapper by {@see MapObject}. Do not edit directly.
 *
 * @implements Mapper<HierarchicalChildOneInput>
 */
class HierarchicalParentInput__HierarchicalChildOneInputMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): HierarchicalChildOneInput
    {
        if (!is_array($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'array');
        }

        if (!array_key_exists('id', $data)) {
            throw MappingFailedException::missingKey($path, 'id');
        }

        if (!array_key_exists('name', $data)) {
            throw MappingFailedException::missingKey($path, 'name');
        }

        if (!array_key_exists('type', $data)) {
            throw MappingFailedException::missingKey($path, 'type');
        }

        if (!array_key_exists('childOneField', $data)) {
            throw MappingFailedException::missingKey($path, 'childOneField');
        }

        $knownKeys = ['id' => true, 'name' => true, 'age' => true, 'type' => true, 'childOneField' => true];
        $extraKeys = array_diff_key($data, $knownKeys);

        if (count($extraKeys) > 0) {
            throw MappingFailedException::extraKeys($path, array_keys($extraKeys));
        }

        return new HierarchicalChildOneInput(
            $this->mapId($data['id'], [...$path, 'id']),
            $this->mapName($data['name'], [...$path, 'name']),
            array_key_exists('age', $data) ? $this->mapAge($data['age'], [...$path, 'age']) : Optional::none($path, 'age'),
            $this->mapType($data['type'], [...$path, 'type']),
            $this->mapChildOneField($data['childOneField'], [...$path, 'childOneField']),
        );
    }

    /**
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    private function mapId(mixed $data, array $path = []): int
    {
        if (!is_int($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'int');
        }

        return $data;
    }

    /**
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    private function mapName(mixed $data, array $path = []): string
    {
        if (!is_string($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'string');
        }

        return $data;
    }

    /**
     * @param  list<string|int> $path
     * @return OptionalSome<int>
     * @throws MappingFailedException
     */
    private function mapAge(mixed $data, array $path = []): OptionalSome
    {
        if (!is_int($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'int');
        }

        return Optional::of($data);
    }

    /**
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    private function mapType(mixed $data, array $path = []): string
    {
        if (!is_string($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'string');
        }

        return $data;
    }

    /**
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    private function mapChildOneField(mixed $data, array $path = []): string
    {
        if (!is_string($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'string');
        }

        return $data;
    }
}
