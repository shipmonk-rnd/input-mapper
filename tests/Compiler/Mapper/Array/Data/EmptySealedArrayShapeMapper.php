<?php declare (strict_types=1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Array\Data;

use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use ShipMonk\InputMapper\Runtime\MappingFailedException;
use function array_diff_key;
use function array_keys;
use function count;
use function implode;
use function is_array;

/**
 * Generated mapper. Do not edit directly.
 *
 * @implements Mapper<array{}>
 */
class EmptySealedArrayShapeMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @param  list<string|int> $path
     * @return array{}
     */
    public function map(mixed $data, array $path = []): array
    {
        if (!is_array($data)) {
            throw new MappingFailedException(
                $data,
                $path,
                'array',
            );
        }

        $mapped = [];
        $knownKeys = [];
        $extraKeys = array_diff_key($data, $knownKeys);

        if (count($extraKeys) > 0) {
            throw new MappingFailedException(
                $data,
                $path,
                'array to not have keys [' . implode(', ', array_keys($extraKeys)) . ']',
            );
        }

        return $mapped;
    }
}
