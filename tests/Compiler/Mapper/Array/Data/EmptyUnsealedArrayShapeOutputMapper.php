<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Array\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Output\ArrayShapeOutputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;

/**
 * Generated mapper by {@see ArrayShapeOutputMapperCompiler}. Do not edit directly.
 *
 * @implements Mapper<array{...}, array{...}>
 */
class EmptyUnsealedArrayShapeOutputMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @param  array{...} $data
     * @param  list<string|int> $path
     * @return array{...}
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): array
    {
        return [];
    }
}
