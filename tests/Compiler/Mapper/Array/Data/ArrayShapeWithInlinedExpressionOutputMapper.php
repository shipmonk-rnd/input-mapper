<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Array\Data;

use ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data\SuitEnum;
use ShipMonk\InputMapper\Compiler\Mapper\Output\ArrayShapeOutputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\OutputMapper;
use ShipMonk\InputMapper\Runtime\OutputMapperProvider;

/**
 * Generated mapper by {@see ArrayShapeOutputMapperCompiler}. Do not edit directly.
 *
 * @implements OutputMapper<array{name: string, suit: SuitEnum}>
 */
class ArrayShapeWithInlinedExpressionOutputMapper implements OutputMapper
{
    public function __construct(private readonly OutputMapperProvider $provider)
    {
    }

    /**
     * @param  array{name: string, suit: SuitEnum} $data
     * @param  list<string|int> $path
     * @return array{name: string, suit: string}
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): mixed
    {
        $mapped = [];
        $mapped['name'] = $data['name'];
        $mapped['suit'] = $data['suit']->value;
        return $mapped;
    }
}
