<?php declare (strict_types=1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Array\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Array\MapArrayShape;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use function is_array;

/**
 * Generated mapper by {@see MapArrayShape}. Do not edit directly.
 *
 * @implements Mapper<array{...}>
 */
class EmptyUnsealedArrayShapeMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @param  list<string|int> $path
     * @return array{...}
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): array
    {
        if (!is_array($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'array');
        }

        $mapped = [];
        return $mapped;
    }
}
