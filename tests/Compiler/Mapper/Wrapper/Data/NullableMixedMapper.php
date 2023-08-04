<?php declare (strict_types=1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Wrapper\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Wrapper\MapNullable;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;

/**
 * Generated mapper by {@see MapNullable}. Do not edit directly.
 *
 * @implements Mapper<mixed>
 */
class NullableMixedMapper implements Mapper
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
        if ($data === null) {
            $mapped = null;
        } else {
            $mapped = $data;
        }

        return $mapped;
    }
}
