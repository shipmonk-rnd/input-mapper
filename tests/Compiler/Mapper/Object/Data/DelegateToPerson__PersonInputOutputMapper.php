<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Output\ObjectOutputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;

/**
 * Generated mapper by {@see ObjectOutputMapperCompiler}. Do not edit directly.
 *
 * @implements Mapper<PersonInput, array{id: int, name: string, age?: int}>
 */
class DelegateToPerson__PersonInputOutputMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @param  PersonInput $data
     * @param  list<string|int> $path
     * @return array{id: int, name: string, age?: int}
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): array
    {
        $output = [];
        $output['id'] = $data->id;
        $output['name'] = $data->name;

        if ($data->age->isDefined()) {
            $output['age'] = $data->age->get();
        }

        return $output;
    }
}
