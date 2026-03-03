<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Validator\Int\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Input\ValidatedInputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\InputMapper;
use ShipMonk\InputMapper\Runtime\InputMapperProvider;
use function is_int;

/**
 * Generated mapper by {@see ValidatedInputMapperCompiler}. Do not edit directly.
 *
 * @implements InputMapper<int<min, 4>>
 */
class IntRangeValidatorWithExclusiveUpperBoundMapper implements InputMapper
{
    public function __construct(private readonly InputMapperProvider $provider)
    {
    }

    /**
     * @param  list<string|int> $path
     * @return int<min, 4>
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): int
    {
        if (!is_int($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'int');
        }

        if ($data >= 5) {
            throw MappingFailedException::incorrectValue($data, $path, 'value less than 5');
        }

        return $data;
    }
}
