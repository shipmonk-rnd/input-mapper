<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Output\ObjectOutputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;

/**
 * Generated mapper by {@see ObjectOutputMapperCompiler}. Do not edit directly.
 *
 * @implements Mapper<PersonWithSourceKeyInput, array{'bom-ref': int, 'x:y': string}>
 */
class PersonWithInvalidIdentifierSourceKeysOutputMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @param  PersonWithSourceKeyInput $data
     * @param  list<string|int> $path
     * @return array{'bom-ref': int, 'x:y': string}
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): array
    {
        return ['bom-ref' => $data->id, 'x:y' => $data->name];
    }
}
