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
use function is_string;

/**
 * Generated mapper by {@see ArrayShapeInputMapperCompiler}. Do not edit directly.
 *
 * @implements Mapper<mixed, array{'bom-ref': int, 'x:y'?: string}>
 */
class ArrayShapeWithInvalidIdentifierKeysMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @param  list<string|int> $path
     * @return array{'bom-ref': int, 'x:y'?: string}
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): array
    {
        if (!is_array($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'array');
        }

        $mapped = [];

        if (!array_key_exists('bom-ref', $data)) {
            throw MappingFailedException::missingKey($path, 'bom-ref');
        }

        $mapped['bom-ref'] = $this->mapBom_ref($data['bom-ref'], [...$path, 'bom-ref']);

        if (array_key_exists('x:y', $data)) {
            $mapped['x:y'] = $this->mapX_y($data['x:y'], [...$path, 'x:y']);
        }

        $knownKeys = ['bom-ref' => true, 'x:y' => true];
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
}
