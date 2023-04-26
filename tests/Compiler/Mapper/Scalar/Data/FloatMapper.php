<?php declare (strict_types=1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Scalar\Data;

use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use ShipMonk\InputMapper\Runtime\MappingFailedException;
use function floatval;
use function is_finite;
use function is_float;
use function is_int;

/**
 * Generated mapper. Do not edit directly.
 *
 * @implements Mapper<float>
 */
class FloatMapper implements Mapper
{
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
            if (!is_finite($data)) {
                throw MappingFailedException::incorrectType($data, $path, 'finite float');
            }

            $mapped = $data;
        } elseif (is_int($data) && $data >= -9007199254740991 && $data <= 9007199254740991) {
            $mapped = floatval($data);
        } else {
            throw MappingFailedException::incorrectType($data, $path, 'float');
        }

        return $mapped;
    }
}
