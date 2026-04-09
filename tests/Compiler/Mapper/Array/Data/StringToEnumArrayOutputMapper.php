<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Array\Data;

use ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data\SuitEnum;
use ShipMonk\InputMapper\Compiler\Mapper\Output\ArrayOutputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;

/**
 * Generated mapper by {@see ArrayOutputMapperCompiler}. Do not edit directly.
 *
 * @implements Mapper<array<string, SuitEnum>, mixed>
 */
class StringToEnumArrayOutputMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @param  array<string, SuitEnum> $data
     * @param  list<string|int> $path
     * @return array<string, string>
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): mixed
    {
        $mapped = [];

        foreach ($data as $key => $value) {
            $mapped[$key] = $value->value;
        }

        return $mapped;
    }
}
