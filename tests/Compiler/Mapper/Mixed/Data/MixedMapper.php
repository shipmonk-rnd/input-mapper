<?php declare (strict_types=1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Mixed\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Mixed\MapMixed;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperContext;
use ShipMonk\InputMapper\Runtime\MapperProvider;

/**
 * Generated mapper by {@see MapMixed}. Do not edit directly.
 *
 * @implements Mapper<mixed>
 */
class MixedMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @throws MappingFailedException
     */
    public function map(mixed $data, ?MapperContext $context = null): mixed
    {
        return $data;
    }
}
