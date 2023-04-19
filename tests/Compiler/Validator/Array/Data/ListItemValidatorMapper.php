<?php declare (strict_types=1);

namespace ShipMonkTests\InputMapper\Compiler\Validator\Array\Data;

use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use ShipMonk\InputMapper\Runtime\MappingFailedException;
use function array_is_list;
use function is_array;
use function is_int;

/**
 * Generated mapper. Do not edit directly.
 *
 * @implements Mapper<mixed>
 */
class ListItemValidatorMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): mixed
    {
        if (is_array($data) && array_is_list($data)) {
            foreach ($data as $index => $item) {
                if (is_int($item)) {
                    if ($item <= 0) {
                        throw MappingFailedException::incorrectValue($item, [...$path, $index], 'value greater than 0');
                    }
                }

            }
        }

        return $data;
    }
}
