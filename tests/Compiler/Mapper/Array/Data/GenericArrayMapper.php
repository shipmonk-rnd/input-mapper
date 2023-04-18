<?php declare (strict_types=1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Array\Data;

use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use ShipMonk\InputMapper\Runtime\MappingFailedException;
use function is_array;
use function is_int;
use function is_string;

/**
 * Generated mapper. Do not edit directly.
 *
 * @implements Mapper<array<string, int>>
 */
class GenericArrayMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @param  list<string|int> $path
     * @return array<string, int>
     */
    public function map(mixed $data, array $path = []): array
    {
        if (!is_array($data)) {
            throw new MappingFailedException(
                $data,
                $path,
                'array',
            );
        }

        $mapped = [];

        foreach ($data as $key => $value) {
            if (!is_string($key)) {
                throw new MappingFailedException(
                    $key,
                    [...$path, $key],
                    'string',
                );
            }

            if (!is_int($value)) {
                throw new MappingFailedException(
                    $value,
                    [...$path, $key],
                    'int',
                );
            }

            $mapped[$key] = $value;
        }

        return $mapped;
    }
}
