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
    private const MIN_SAFE_INTEGER = -9007199254740991;

    private const MAX_SAFE_INTEGER = 9007199254740991;

    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): float
    {
        if (is_float($data)) {
            if (is_nan($data)) {
                throw MappingFailedException::incorrectType($data, $path, 'finite float or INF');
            }

            $mapped = $data;
        } elseif (is_int($data)) {
            if ($data < self::MIN_SAFE_INTEGER || $data > self::MAX_SAFE_INTEGER) {
                throw MappingFailedException::incorrectValue($data, $path, 'float or int with value that can be losslessly converted to float');
            }

            $mapped = floatval($data);
        } else {
            throw MappingFailedException::incorrectType($data, $path, 'float');
        }

        return $mapped;
    }
}
