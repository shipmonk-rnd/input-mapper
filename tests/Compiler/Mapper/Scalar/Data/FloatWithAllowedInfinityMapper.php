<?php declare (strict_types=1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Scalar\Data;

use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use ShipMonk\InputMapper\Runtime\MappingFailedException;
use function floatval;
use function is_float;
use function is_int;
use function is_nan;

/**
 * Generated mapper. Do not edit directly.
 *
 * @implements Mapper<float>
 */
class FloatWithAllowedInfinityMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @param  list<string|int> $path
     */
    public function map(mixed $data, array $path = []): float
    {
        if (!is_float($data) && !is_int($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'float');
        }

        if (is_nan($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'finite float or INF');
        }

        return floatval($data);
    }
}