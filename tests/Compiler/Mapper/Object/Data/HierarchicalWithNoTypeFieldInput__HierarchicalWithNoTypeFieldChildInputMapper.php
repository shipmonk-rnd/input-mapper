<?php declare (strict_types=1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Object\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Object\MapObject;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use function array_key_exists;
use function is_array;
use function is_int;
use function is_string;

/**
 * Generated mapper by {@see MapObject}. Do not edit directly.
 *
 * @implements Mapper<HierarchicalWithNoTypeFieldChildInput>
 */
class HierarchicalWithNoTypeFieldInput__HierarchicalWithNoTypeFieldChildInputMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): HierarchicalWithNoTypeFieldChildInput
    {
        if (!is_array($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'array');
        }

        if (!array_key_exists('id', $data)) {
            throw MappingFailedException::missingKey($path, 'id');
        }

        if (!array_key_exists('childOneField', $data)) {
            throw MappingFailedException::missingKey($path, 'childOneField');
        }

        return new HierarchicalWithNoTypeFieldChildInput(
            $this->mapId($data['id'], [...$path, 'id']),
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
    private function mapChildOneField(mixed $data, array $path = []): string
    {
        if (!is_string($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'string');
        }

        return $data;
    }
}
