<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Mixed\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Input\MixedInputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\InputMapper;
use ShipMonk\InputMapper\Runtime\InputMapperProvider;

/**
 * Generated mapper by {@see MixedInputMapperCompiler}. Do not edit directly.
 *
 * @implements InputMapper<mixed>
 */
class MixedMapper implements InputMapper
{
    public function __construct(private readonly InputMapperProvider $provider)
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
