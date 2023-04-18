<?php declare (strict_types=1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Array\Data;

use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use ShipMonk\InputMapper\Runtime\MappingFailedException;
use function array_is_list;
use function is_array;
use function is_int;

/**
 * Generated mapper. Do not edit directly.
 *
 * @implements Mapper<list<int>>
 */
class GenericListMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @param  list<string|int> $path
     * @return list<int>
     */
    public function map(mixed $data, array $path = []): array
    {
        if (!is_array($data) || !array_is_list($data)) {
            throw new MappingFailedException(
                $data,
                $path,
                'list',
            );
        }

        $mapped = [];

        foreach ($data as $index => $item) {
            if (!is_int($item)) {
                throw new MappingFailedException(
                    $item,
                    [...$path, $index],
                    'int',
                );
            }

            $mapped[] = $item;
        }

        return $mapped;
    }
}
