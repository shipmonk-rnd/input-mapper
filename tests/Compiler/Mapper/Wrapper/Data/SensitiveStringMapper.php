<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Wrapper\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Input\SensitiveInputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use function is_string;

/**
 * Generated mapper by {@see SensitiveInputMapperCompiler}. Do not edit directly.
 *
 * @implements Mapper<mixed, string>
 */
class SensitiveStringMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): string
    {
        try {
            if (!is_string($data)) {
                throw MappingFailedException::incorrectType($data, $path, 'string');
            }

            $mapped = $data;
        } catch (MappingFailedException $e) {
            throw MappingFailedException::redact($e);
        }
        return $mapped;
    }
}
