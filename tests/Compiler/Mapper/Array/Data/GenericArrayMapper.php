<?php declare (strict_types=1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Array\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Array\MapArray;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperContext;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use function is_array;
use function is_int;
use function is_string;

/**
 * Generated mapper by {@see MapArray}. Do not edit directly.
 *
 * @implements Mapper<array<string, int>>
 */
class GenericArrayMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @return array<string, int>
     * @throws MappingFailedException
     */
    public function map(mixed $data, ?MapperContext $context = null): array
    {
        if (!is_array($data)) {
            throw MappingFailedException::incorrectType($data, $context, 'array');
        }

        $mapped = [];

        foreach ($data as $key => $value) {
            if (!is_string($key)) {
                throw MappingFailedException::incorrectType($key, MapperContext::append($context, $key), 'string');
            }

            if (!is_int($value)) {
                throw MappingFailedException::incorrectType($value, MapperContext::append($context, $key), 'int');
            }

            $mapped[$key] = $value;
        }

        return $mapped;
    }
}
