<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Validator\Array\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Input\ValidatedInputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\InputMapper;
use ShipMonk\InputMapper\Runtime\InputMapperProvider;
use function array_is_list;
use function is_array;
use function is_int;

/**
 * Generated mapper by {@see ValidatedInputMapperCompiler}. Do not edit directly.
 *
 * @implements InputMapper<list<positive-int>>
 */
class ListItemValidatorWithMultipleValidatorsMapper implements InputMapper
{
    public function __construct(private readonly InputMapperProvider $provider)
    {
    }

    /**
     * @param  list<string|int> $path
     * @return list<positive-int>
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

            if ($item2 % 5 !== 0) {
                throw MappingFailedException::incorrectValue($item2, [...$path, $index2], 'multiple of 5');
            }
        }

        return $mapped;
    }
}
