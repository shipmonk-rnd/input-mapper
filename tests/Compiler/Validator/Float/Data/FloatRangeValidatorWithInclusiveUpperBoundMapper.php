<?php declare (strict_types=1);

namespace ShipMonkTests\InputMapper\Compiler\Validator\Float\Data;

use Nette\Utils\Floats;
use ShipMonk\InputMapper\Compiler\Mapper\Wrapper\ValidatedMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use function floatval;
use function is_finite;
use function is_float;
use function is_int;

/**
 * Generated mapper by {@see ValidatedMapperCompiler}. Do not edit directly.
 *
 * @implements Mapper<float>
 */
class FloatRangeValidatorWithInclusiveUpperBoundMapper implements Mapper
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
            if (!is_finite($data)) {
                throw MappingFailedException::incorrectType($data, $path, 'finite float');
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

        if (Floats::isGreaterThan($mapped, 5.0)) {
            throw MappingFailedException::incorrectValue($mapped, $path, 'value less than or equal to 5');
        }

        return $mapped;
    }
}
