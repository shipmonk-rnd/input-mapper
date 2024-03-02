<?php declare (strict_types=1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Array\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Array\MapArrayShape;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperContext;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use function array_diff_key;
use function array_keys;
use function count;
use function is_array;

/**
 * Generated mapper by {@see MapArrayShape}. Do not edit directly.
 *
 * @implements Mapper<array{}>
 */
class EmptySealedArrayShapeMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @return array{}
     * @throws MappingFailedException
     */
    public function map(mixed $data, ?MapperContext $context = null): array
    {
        if (!is_array($data)) {
            throw MappingFailedException::incorrectType($data, $context, 'array');
        }

        $mapped = [];
        $knownKeys = [];
        $extraKeys = array_diff_key($data, $knownKeys);

        if (count($extraKeys) > 0) {
            throw MappingFailedException::extraKeys($context, array_keys($extraKeys));
        }

        return $mapped;
    }
}
