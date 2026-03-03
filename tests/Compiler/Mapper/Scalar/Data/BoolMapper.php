<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Scalar\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Input\BoolInputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\InputMapper;
use ShipMonk\InputMapper\Runtime\InputMapperProvider;
use function is_bool;

/**
 * Generated mapper by {@see BoolInputMapperCompiler}. Do not edit directly.
 *
 * @implements InputMapper<bool>
 */
class BoolMapper implements InputMapper
{
    public function __construct(private readonly InputMapperProvider $provider)
    {
    }

    /**
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): bool
    {
        if (!is_bool($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'bool');
        }

        return $data;
    }
}
