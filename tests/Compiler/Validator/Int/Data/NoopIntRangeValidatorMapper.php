<?php declare (strict_types=1);

namespace ShipMonkTests\InputMapper\Compiler\Validator\Int\Data;

use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use ShipMonk\InputMapper\Runtime\MappingFailedException;

/**
 * Generated mapper. Do not edit directly.
 *
 * @implements Mapper<mixed>
 */
class NoopIntRangeValidatorMapper implements Mapper
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
        return $data;
    }
}
