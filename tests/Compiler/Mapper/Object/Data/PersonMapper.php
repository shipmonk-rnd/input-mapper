<?php declare (strict_types=1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Object\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Object\MapObject;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperContext;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use ShipMonk\InputMapper\Runtime\Optional;
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
 * @implements Mapper<PersonInput>
 */
class PersonMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @throws MappingFailedException
     */
    public function map(mixed $data, ?MapperContext $context = null): PersonInput
    {
        if (!is_array($data)) {
            throw MappingFailedException::incorrectType($data, $context, 'array');
        }

        if (!array_key_exists('id', $data)) {
            throw MappingFailedException::missingKey($context, 'id');
        }

        if (!array_key_exists('name', $data)) {
            throw MappingFailedException::missingKey($context, 'name');
        }

        $knownKeys = ['id' => true, 'name' => true, 'age' => true];
        $extraKeys = array_diff_key($data, $knownKeys);

        if (count($extraKeys) > 0) {
            throw MappingFailedException::extraKeys($context, array_keys($extraKeys));
        }

        return new PersonInput(
            $this->mapId($data['id'], MapperContext::append($context, 'id')),
            $this->mapName($data['name'], MapperContext::append($context, 'name')),
            array_key_exists('age', $data) ? $this->mapAge($data['age'], MapperContext::append($context, 'age')) : Optional::none($context, 'age'),
        );
    }

    /**
     * @throws MappingFailedException
     */
    private function mapId(mixed $data, ?MapperContext $context = null): int
    {
        if (!is_int($data)) {
            throw MappingFailedException::incorrectType($data, $context, 'int');
        }

        return $data;
    }

    /**
     * @throws MappingFailedException
     */
    private function mapName(mixed $data, ?MapperContext $context = null): string
    {
        if (!is_string($data)) {
            throw MappingFailedException::incorrectType($data, $context, 'string');
        }

        return $data;
    }

    /**
     * @return Optional<int>
     * @throws MappingFailedException
     */
    private function mapAge(mixed $data, ?MapperContext $context = null): Optional
    {
        if (!is_int($data)) {
            throw MappingFailedException::incorrectType($data, $context, 'int');
        }

        return Optional::of($data);
    }
}
