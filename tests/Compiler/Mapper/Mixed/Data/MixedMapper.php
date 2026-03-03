<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Mixed\Data;

use ShipMonk\InputMapper\Compiler\Attribute\MapMixed;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;

/**
 * Generated mapper by {@see MapMixed}. Do not edit directly.
 *
 * @implements Mapper<mixed>
 */
class MixedMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): mixed
    {
        return $data;
    }
}
