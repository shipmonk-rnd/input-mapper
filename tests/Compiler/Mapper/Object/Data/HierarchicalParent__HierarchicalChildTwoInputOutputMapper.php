<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Output\ObjectOutputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;

/**
 * Generated mapper by {@see ObjectOutputMapperCompiler}. Do not edit directly.
 *
 * @implements Mapper<HierarchicalChildTwoInput, mixed>
 */
class HierarchicalParent__HierarchicalChildTwoInputOutputMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @param  HierarchicalChildTwoInput $data
     * @param  list<string|int> $path
     * @return array{id: int, name: string, age?: int, type: string, childTwoField: int}
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): mixed
    {
        $output = [];
        $output['id'] = $data->id;
        $output['name'] = $data->name;

        if ($data->age->isDefined()) {
            $output['age'] = $data->age->get();
        }

        $output['type'] = $data->type;
        $output['childTwoField'] = $data->childTwoField;
        return $output;
    }
}
