<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Array\Data;

use ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data\SuitEnum;
use ShipMonk\InputMapper\Compiler\Mapper\Output\ArrayShapeOutputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use function array_key_exists;

/**
 * Generated mapper by {@see ArrayShapeOutputMapperCompiler}. Do not edit directly.
 *
 * @implements Mapper<array{'bom-ref': string, 'x:y'?: list<SuitEnum>}, array{'bom-ref': string, 'x:y'?: list<string>}>
 */
class ArrayShapeWithInvalidIdentifierKeysOutputMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @param  array{'bom-ref': string, 'x:y'?: list<SuitEnum>} $data
     * @param  list<string|int> $path
     * @return array{'bom-ref': string, 'x:y'?: list<string>}
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): array
    {
        $mapped = [];
        $mapped['bom-ref'] = $data['bom-ref'];

        if (array_key_exists('x:y', $data)) {
            $mapped['x:y'] = $this->mapX_y($data['x:y'], [...$path, 'x:y']);
        }

        return $mapped;
    }

    /**
     * @param  list<SuitEnum> $data
     * @param  list<string|int> $path
     * @return list<string>
     * @throws MappingFailedException
     */
    private function mapX_y(mixed $data, array $path = []): array
    {
        $mapped = [];

        foreach ($data as $index => $item) {
            $mapped[] = $item->value;
        }

        return $mapped;
    }
}
