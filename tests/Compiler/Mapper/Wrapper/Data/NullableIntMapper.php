<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Wrapper\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Input\NullableInputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\InputMapper;
use ShipMonk\InputMapper\Runtime\InputMapperProvider;
use function is_int;

/**
 * Generated mapper by {@see NullableInputMapperCompiler}. Do not edit directly.
 *
 * @implements InputMapper<?int>
 */
class NullableIntMapper implements InputMapper
{
    public function __construct(private readonly InputMapperProvider $provider)
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
