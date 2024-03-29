<?php declare (strict_types=1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Wrapper\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Wrapper\MapNullable;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use function is_int;

/**
 * Generated mapper by {@see MapNullable}. Do not edit directly.
 *
 * @implements Mapper<?int>
 */
class NullableIntMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): ?int
    {
        if ($data === null) {
            $mapped = null;
        } else {
            if (!is_int($data)) {
                throw MappingFailedException::incorrectType($data, $path, 'int');
            }

            $mapped = $data;
        }

        return $mapped;
    }
}
