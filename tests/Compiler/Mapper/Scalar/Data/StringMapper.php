<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Scalar\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Input\StringInputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\InputMapper;
use ShipMonk\InputMapper\Runtime\InputMapperProvider;
use function is_string;

/**
 * Generated mapper by {@see StringInputMapperCompiler}. Do not edit directly.
 *
 * @implements InputMapper<string>
 */
class StringMapper implements InputMapper
{
    public function __construct(private readonly InputMapperProvider $provider)
    {
    }

    /**
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): string
    {
        if (!is_string($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'string');
        }

        return $data;
    }
}
