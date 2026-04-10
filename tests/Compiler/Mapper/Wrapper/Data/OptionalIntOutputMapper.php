<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Wrapper\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Output\OptionalOutputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use ShipMonk\InputMapper\Runtime\Optional;

/**
 * Generated mapper by {@see OptionalOutputMapperCompiler}. Do not edit directly.
 *
 * @implements Mapper<Optional<int>, int>
 */
class OptionalIntOutputMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @param  Optional<int> $data
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): int
    {
        return $data->get();
    }
}
