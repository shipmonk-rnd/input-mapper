<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Validator\Array\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Input\ValidatedInputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\InputMapper;
use ShipMonk\InputMapper\Runtime\InputMapperProvider;
use function array_is_list;
use function count;
use function is_array;
use function is_string;

/**
 * Generated mapper by {@see ValidatedInputMapperCompiler}. Do not edit directly.
 *
 * @implements InputMapper<non-empty-list<string>>
 */
class ListLengthValidatorWithMinMapper implements InputMapper
{
    public function __construct(private readonly InputMapperProvider $provider)
    {
    }

    /**
     * @param  list<string|int> $path
     * @return non-empty-list<string>
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): array
    {
        if (!is_array($data) || !array_is_list($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'list');
        }

        $mapped = [];

        foreach ($data as $index => $item) {
            if (!is_string($item)) {
                throw MappingFailedException::incorrectType($item, [...$path, $index], 'string');
            }

            $mapped[] = $item;
        }

        if (count($mapped) < 2) {
            throw MappingFailedException::incorrectValue($mapped, $path, 'list with at least 2 items');
        }

        return $mapped;
    }
}
