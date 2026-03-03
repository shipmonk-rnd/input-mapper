<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Wrapper\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Output\OptionalOutputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Optional;
use ShipMonk\InputMapper\Runtime\OutputMapper;
use ShipMonk\InputMapper\Runtime\OutputMapperProvider;

/**
 * Generated mapper by {@see OptionalOutputMapperCompiler}. Do not edit directly.
 *
 * @implements OutputMapper<Optional<int>>
 */
class OptionalIntOutputMapper implements OutputMapper
{
    public function __construct(private readonly OutputMapperProvider $provider)
    {
    }

    /**
     * @param  Optional<int> $data
     * @param  list<string|int> $path
     * @return int
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): mixed
    {
        return $data->get();
    }
}
