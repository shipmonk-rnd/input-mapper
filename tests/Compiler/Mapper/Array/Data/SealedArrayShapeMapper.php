<?php declare (strict_types=1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Array\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Array\MapArrayShape;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperContext;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use function array_diff_key;
use function array_key_exists;
use function array_keys;
use function count;
use function is_array;
use function is_int;
use function is_string;

/**
 * Generated mapper by {@see MapArrayShape}. Do not edit directly.
 *
 * @implements Mapper<array{a: int, b?: string}>
 */
class SealedArrayShapeMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @return array{a: int, b?: string}
     * @throws MappingFailedException
     */
    public function map(mixed $data, ?MapperContext $context = null): array
    {
        if (!is_array($data)) {
            throw MappingFailedException::incorrectType($data, $context, 'array');
        }

        $mapped = [];

        if (!array_key_exists('a', $data)) {
            throw MappingFailedException::missingKey($context, 'a');
        }

        $mapped['a'] = $this->mapA($data['a'], MapperContext::append($context, 'a'));

        if (array_key_exists('b', $data)) {
            $mapped['b'] = $this->mapB($data['b'], MapperContext::append($context, 'b'));
        }

        $knownKeys = ['a' => true, 'b' => true];
        $extraKeys = array_diff_key($data, $knownKeys);

        if (count($extraKeys) > 0) {
            throw MappingFailedException::extraKeys($context, array_keys($extraKeys));
        }

        return $mapped;
    }

    /**
     * @throws MappingFailedException
     */
    private function mapA(mixed $data, ?MapperContext $context = null): int
    {
        if (!is_int($data)) {
            throw MappingFailedException::incorrectType($data, $context, 'int');
        }

        return $data;
    }

    /**
     * @throws MappingFailedException
     */
    private function mapB(mixed $data, ?MapperContext $context = null): string
    {
        if (!is_string($data)) {
            throw MappingFailedException::incorrectType($data, $context, 'string');
        }

        return $data;
    }
}
