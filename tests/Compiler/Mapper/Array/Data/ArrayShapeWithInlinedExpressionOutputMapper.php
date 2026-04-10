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
 * @implements Mapper<array{name: string, suit: SuitEnum}, array{name: string, suit: string}>
 */
class ArrayShapeWithInlinedExpressionOutputMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @param  array{name: string, suit: SuitEnum} $data
     * @param  list<string|int> $path
     * @return array{name: string, suit: string}
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): array
    {
        return ['name' => $data['name'], 'suit' => $data['suit']->value];
    }
}
