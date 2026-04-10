<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Output\ObjectOutputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;

/**
 * Generated mapper by {@see ObjectOutputMapperCompiler}. Do not edit directly.
 *
 * @implements Mapper<PersonWithSourceKeyInput, array{id: int, full_name: string}>
 */
class PersonWithSourceKeyOutputMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @param  PersonWithSourceKeyInput $data
     * @param  list<string|int> $path
     * @return array{id: int, full_name: string}
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): array
    {
        return ['id' => $data->id, 'full_name' => $data->name];
    }
}
