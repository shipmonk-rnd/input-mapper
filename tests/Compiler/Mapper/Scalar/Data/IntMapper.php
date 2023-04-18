<?php declare (strict_types=1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Scalar\Data;

use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use ShipMonk\InputMapper\Runtime\MappingFailedException;
use function is_int;

/**
 * Generated mapper. Do not edit directly.
 *
 * @implements Mapper<int>
 */
class IntMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @param  list<string|int> $path
     */
    public function map(mixed $data, array $path = []): int
    {
        if (!is_int($data)) {
            throw new MappingFailedException(
                $data,
                $path,
                'int',
            );
        }

        return $data;
    }
}
