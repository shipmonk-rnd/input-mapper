<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Wrapper\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Output\NullableOutputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\OutputMapper;
use ShipMonk\InputMapper\Runtime\OutputMapperProvider;

/**
 * Generated mapper by {@see NullableOutputMapperCompiler}. Do not edit directly.
 *
 * @implements OutputMapper<?int>
 */
class NullableIntOutputMapper implements OutputMapper
{
    public function __construct(private readonly OutputMapperProvider $provider)
    {
    }

    /**
     * @param  ?int $data
     * @param  list<string|int> $path
     * @return ?int
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
