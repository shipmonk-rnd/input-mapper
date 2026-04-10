<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Array\Data;

use ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data\SuitEnum;
use ShipMonk\InputMapper\Compiler\Mapper\Output\ArrayShapeOutputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;

/**
 * Generated mapper by {@see ArrayShapeOutputMapperCompiler}. Do not edit directly.
 *
 * @implements Mapper<array{name: string, suits: list<SuitEnum>}, array{name: string, suits: list<string>}>
 */
class ArrayShapeWithStatementsOutputMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @param  array{name: string, suits: list<SuitEnum>} $data
     * @param  list<string|int> $path
     * @return array{name: string, suits: list<string>}
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): array
    {
        return ['name' => $data['name'], 'suits' => $this->mapSuits($data['suits'], [...$path, 'suits'])];
    }

    /**
     * @param  list<SuitEnum> $data
     * @param  list<string|int> $path
     * @return list<string>
     * @throws MappingFailedException
     */
    private function mapSuits(mixed $data, array $path = []): array
    {
        $mapped = [];

        foreach ($data as $index => $item) {
            $mapped[] = $item->value;
        }

        return $mapped;
    }
}
