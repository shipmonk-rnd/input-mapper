<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Input\ObjectInputMapperCompiler;
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
 * Generated mapper by {@see ObjectInputMapperCompiler}. Do not edit directly.
 *
 * @implements Mapper<mixed, PersonInput>
 */
class PersonWithSourceKeysWhichDifferOnlyInCaseMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): PersonInput
    {
        if (!is_array($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'array');
        }

        if (!array_key_exists('id', $data)) {
            throw MappingFailedException::missingKey($path, 'id');
        }

        if (!array_key_exists('ID', $data)) {
            throw MappingFailedException::missingKey($path, 'ID');
        }

        $knownKeys = ['id' => true, 'ID' => true, 'Id' => true];
        $extraKeys = array_diff_key($data, $knownKeys);

        if (count($extraKeys) > 0) {
            throw MappingFailedException::extraKeys($path, array_keys($extraKeys));
        }

        return new PersonInput(
            $this->mapId($data['id'], [...$path, 'id']),
            $this->mapID2($data['ID'], [...$path, 'ID']),
            array_key_exists('Id', $data) ? $this->mapId3($data['Id'], [...$path, 'Id']) : Optional::none($path, 'Id'),
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
    private function mapID2(mixed $data, array $path = []): string
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
    private function mapId3(mixed $data, array $path = []): OptionalSome
    {
        if (!is_int($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'int');
        }

        return Optional::of($data);
    }
}
