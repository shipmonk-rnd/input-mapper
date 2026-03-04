<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Output\ObjectOutputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\OutputMapper;
use ShipMonk\InputMapper\Runtime\OutputMapperProvider;

/**
 * Generated mapper by {@see ObjectOutputMapperCompiler}. Do not edit directly.
 *
 * @implements OutputMapper<PersonWithNullableAgeInput>
 */
class PersonWithNullableAgeOutputMapper implements OutputMapper
{
    public function __construct(private readonly OutputMapperProvider $provider)
    {
    }

    /**
     * @param  PersonWithNullableAgeInput $data
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): mixed
    {
        return ['id' => $data->id, 'name' => $data->name, 'age' => $this->mapAge($data->age, [...$path, 'age'])];
    }

    /**
     * @param  ?int $data
     * @param  list<string|int> $path
     * @return ?int
     * @throws MappingFailedException
     */
    private function mapAge(mixed $data, array $path = []): mixed
    {
        if ($data === null) {
            $mapped = null;
        } else {
            $mapped = $data;
        }

        return $mapped;
    }
}
