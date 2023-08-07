<?php declare (strict_types=1);

namespace ShipMonkTests\InputMapper\Compiler\Validator\Array\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Wrapper\ValidatedMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use function array_is_list;
use function is_array;
use function is_int;

/**
 * Generated mapper by {@see ValidatedMapperCompiler}. Do not edit directly.
 *
 * @implements Mapper<list<int>>
 */
class ListItemValidatorMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @param  list<string|int> $path
     * @return list<int>
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): array
    {
        if (!is_array($data) || !array_is_list($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'list');
        }

        $mapped = [];

        foreach ($data as $index => $item) {
            if (!is_int($item)) {
                throw MappingFailedException::incorrectType($item, [...$path, $index], 'int');
            }

            $mapped[] = $item;
        }

        foreach ($mapped as $index2 => $item2) {
            if ($item2 <= 0) {
                throw MappingFailedException::incorrectValue($item2, [...$path, $index2], 'value greater than 0');
            }
        }

        return $mapped;
    }
}
