<?php declare (strict_types=1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Scalar\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapBool;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperContext;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use function is_bool;

/**
 * Generated mapper by {@see MapBool}. Do not edit directly.
 *
 * @implements Mapper<bool>
 */
class BoolMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @throws MappingFailedException
     */
    public function map(mixed $data, ?MapperContext $context = null): bool
    {
        if (!is_bool($data)) {
            throw MappingFailedException::incorrectType($data, $context, 'bool');
        }

        return $data;
    }
}
