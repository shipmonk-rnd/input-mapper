<?php declare (strict_types=1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Object\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Object\MapObject;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use function array_column;
use function array_diff_key;
use function array_key_exists;
use function array_keys;
use function count;
use function implode;
use function is_array;
use function is_int;
use function is_string;

/**
 * Generated mapper by {@see MapObject}. Do not edit directly.
 *
 * @implements Mapper<HierarchicalWithEnumChildInput>
 */
class HierarchicalWithEnumParentInput__HierarchicalWithEnumChildInputMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): HierarchicalWithEnumChildInput
    {
        if (!is_array($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'array');
        }

        if (!array_key_exists('id', $data)) {
            throw MappingFailedException::missingKey($path, 'id');
        }

        if (!array_key_exists('type', $data)) {
            throw MappingFailedException::missingKey($path, 'type');
        }

        $knownKeys = ['id' => true, 'type' => true];
        $extraKeys = array_diff_key($data, $knownKeys);

        if (count($extraKeys) > 0) {
            throw MappingFailedException::extraKeys($path, array_keys($extraKeys));
        }

        return new HierarchicalWithEnumChildInput(
            $this->mapId($data['id'], [...$path, 'id']),
            $this->mapType($data['type'], [...$path, 'type']),
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
    private function mapType(mixed $data, array $path = []): HierarchicalWithEnumType
    {
        if (!is_string($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'string');
        }

        $enum = HierarchicalWithEnumType::tryFrom($data);

        if ($enum === null) {
            throw MappingFailedException::incorrectValue($data, $path, 'one of ' . implode(', ', array_column(HierarchicalWithEnumType::cases(), 'value')));
        }

        return $enum;
    }
}
