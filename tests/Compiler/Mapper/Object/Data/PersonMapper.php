<?php declare (strict_types=1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Object\Data;

use ShipMonkTests\InputMapper\Compiler\Mapper\Object\Data\PersonInput;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use ShipMonk\InputMapper\Runtime\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Optional;
use function array_diff_key;
use function array_key_exists;
use function array_keys;
use function count;
use function implode;
use function is_array;
use function is_int;
use function is_string;

/**
 * Generated mapper. Do not edit directly.
 *
 * @implements Mapper<PersonInput>
 */
class PersonMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @param  list<string|int> $path
     */
    public function map(mixed $data, array $path = []): PersonInput
    {
        if (!is_array($data)) {
            throw new MappingFailedException(
                $data,
                $path,
                'array',
            );
        }

        if (!array_key_exists('id', $data)) {
            throw new MappingFailedException(
                $data,
                $path,
                'property id to exist',
            );
        }

        if (!array_key_exists('name', $data)) {
            throw new MappingFailedException(
                $data,
                $path,
                'property name to exist',
            );
        }

        $knownKeys = ['id' => true, 'name' => true, 'age' => true];
        $extraKeys = array_diff_key($data, $knownKeys);

        if (count($extraKeys) > 0) {
            throw new MappingFailedException(
                $data,
                $path,
                'array to not have keys [' . implode(', ', array_keys($extraKeys)) . ']',
            );
        }

        return new PersonInput(
            $this->mapId($data['id'], [...$path, 'id']),
            $this->mapName($data['name'], [...$path, 'name']),
            array_key_exists('age', $data) ? $this->mapAge($data['age'], [...$path, 'age']) : Optional::none(),
        );
    }

    /**
     * @param  list<string|int> $path
     * @return Optional<int>
     */
    private function mapAge(mixed $data, array $path = []): Optional
    {
        if (!is_int($data)) {
            throw new MappingFailedException(
                $data,
                $path,
                'int',
            );
        }

        return Optional::of($data);
    }

    /**
     * @param  list<string|int> $path
     */
    private function mapName(mixed $data, array $path = []): string
    {
        if (!is_string($data)) {
            throw new MappingFailedException(
                $data,
                $path,
                'string',
            );
        }

        return $data;
    }

    /**
     * @param  list<string|int> $path
     */
    private function mapId(mixed $data, array $path = []): int
    {
        if (!is_int($data)) {
            throw new MappingFailedException(
                $data,
                $path,
                'int',
            );
        }

        return $data;
    }
}
