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
class PersonWithInvalidIdentifierSourceKeysMapper implements Mapper
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

        if (!array_key_exists('bom-ref', $data)) {
            throw MappingFailedException::missingKey($path, 'bom-ref');
        }

        if (!array_key_exists('x:y', $data)) {
            throw MappingFailedException::missingKey($path, 'x:y');
        }

        $knownKeys = ['bom-ref' => true, 'x:y' => true, '' => true];
        $extraKeys = array_diff_key($data, $knownKeys);

        if (count($extraKeys) > 0) {
            throw MappingFailedException::extraKeys($path, array_keys($extraKeys));
        }

        return new PersonInput(
            $this->mapBom_ref($data['bom-ref'], [...$path, 'bom-ref']),
            $this->mapX_y($data['x:y'], [...$path, 'x:y']),
            array_key_exists('', $data) ? $this->map2($data[''], [...$path, '']) : Optional::none($path, ''),
        );
    }

    /**
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    private function mapBom_ref(mixed $data, array $path = []): int
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
    private function mapX_y(mixed $data, array $path = []): string
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
    private function map2(mixed $data, array $path = []): OptionalSome
    {
        if (!is_int($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'int');
        }

        return Optional::of($data);
    }
}
