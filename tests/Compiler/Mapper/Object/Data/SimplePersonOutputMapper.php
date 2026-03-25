<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Output\ObjectOutputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\OutputMapper;
use ShipMonk\InputMapper\Runtime\OutputMapperProvider;

/**
 * Generated mapper by {@see ObjectOutputMapperCompiler}. Do not edit directly.
 *
 * @implements OutputMapper<SimplePersonInput>
 */
class SimplePersonOutputMapper implements OutputMapper
{
    public function __construct(private readonly OutputMapperProvider $provider)
    {
    }

    /**
     * @param  SimplePersonInput $data
     * @param  list<string|int> $path
     * @return array{id: int, name: string}
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): mixed
    {
        return ['id' => $data->id, 'name' => $data->name];
    }
}
