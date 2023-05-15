<?php declare (strict_types=1);

namespace ShipMonkTests\InputMapper\Compiler\Validator\Float\Data;

use Nette\Utils\Floats;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use function is_float;

/**
 * Generated mapper. Do not edit directly.
 *
 * @implements Mapper<mixed>
 */
class FloatRangeValidatorWithExclusiveUpperBoundMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): mixed
    {
        if (is_float($data)) {
            if (Floats::isGreaterThanOrEqualTo($data, 5.0)) {
                throw MappingFailedException::incorrectValue($data, $path, 'value less than 5');
            }
        }

        return $data;
    }
}
