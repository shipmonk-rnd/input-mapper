<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Data;

use ShipMonk\InputMapper\Compiler\Mapper\PassthroughMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;

/**
 * Generated mapper by {@see PassthroughMapperCompiler}. Do not edit directly.
 *
 * @implements Mapper<int, mixed>
 */
class PassthroughOutputMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @param  int $data
     * @param  list<string|int> $path
     * @return int
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): mixed
    {
        return $data;
    }
}
