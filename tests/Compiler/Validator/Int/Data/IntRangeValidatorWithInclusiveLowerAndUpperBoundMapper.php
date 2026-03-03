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
 * @implements InputMapper<int<5, 10>>
 */
class IntRangeValidatorWithInclusiveLowerAndUpperBoundMapper implements InputMapper
{
    public function __construct(private readonly InputMapperProvider $provider)
    {
    }

    /**
     * @param  list<string|int> $path
     * @return int<5, 10>
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): int
    {
        if (!is_int($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'int');
        }

        if ($data < 5) {
            throw MappingFailedException::incorrectValue($data, $path, 'value greater than or equal to 5');
        }

        if ($data > 10) {
            throw MappingFailedException::incorrectValue($data, $path, 'value less than or equal to 10');
        }

        return $data;
    }
}
