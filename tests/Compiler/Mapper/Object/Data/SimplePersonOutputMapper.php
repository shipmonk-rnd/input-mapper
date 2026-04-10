<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Output\ObjectOutputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;

/**
 * Generated mapper by {@see ObjectOutputMapperCompiler}. Do not edit directly.
 *
 * @implements Mapper<SimplePersonInput, array{id: int, name: string}>
 */
class SimplePersonOutputMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @param  SimplePersonInput $data
     * @param  list<string|int> $path
     * @return array{id: int, name: string}
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): array
    {
        return ['id' => $data->id, 'name' => $data->name];
    }
}
