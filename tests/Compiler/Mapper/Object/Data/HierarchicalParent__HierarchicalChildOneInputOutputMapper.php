<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Output\ObjectOutputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\OutputMapper;
use ShipMonk\InputMapper\Runtime\OutputMapperProvider;

/**
 * Generated mapper by {@see ObjectOutputMapperCompiler}. Do not edit directly.
 *
 * @implements OutputMapper<HierarchicalChildOneInput>
 */
class HierarchicalParent__HierarchicalChildOneInputOutputMapper implements OutputMapper
{
    public function __construct(private readonly OutputMapperProvider $provider)
    {
    }

    /**
     * @param  HierarchicalChildOneInput $data
     * @param  list<string|int> $path
     * @return array{id: int, name: string, age?: int, type: string, childOneField: string}
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
        $output['childOneField'] = $data->childOneField;
        return $output;
    }
}
