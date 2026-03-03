<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Validator\Array\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Input\ValidatedInputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\InputMapper;
use ShipMonk\InputMapper\Runtime\InputMapperProvider;
use function array_is_list;
use function count;
use function is_array;

/**
 * Generated mapper by {@see ValidatedInputMapperCompiler}. Do not edit directly.
 *
 * @implements InputMapper<non-empty-list<mixed>>
 */
class ListLengthValidatorWithExactMapper implements InputMapper
{
    public function __construct(private readonly InputMapperProvider $provider)
    {
    }

    /**
     * @param  list<string|int> $path
     * @return non-empty-list<mixed>
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): array
    {
        if (!is_array($data) || !array_is_list($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'list');
        }

        $mapped = [];

        foreach ($data as $index => $item) {
            $mapped[] = $item;
        }

        if (count($mapped) !== 5) {
            throw MappingFailedException::incorrectValue($mapped, $path, 'list with exactly 5 items');
        }

        return $mapped;
    }
}
