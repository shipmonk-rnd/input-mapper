<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Validator\String\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Input\ValidatedInputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\InputMapper;
use ShipMonk\InputMapper\Runtime\InputMapperProvider;
use function is_string;
use function strlen;

/**
 * Generated mapper by {@see ValidatedInputMapperCompiler}. Do not edit directly.
 *
 * @implements InputMapper<string>
 */
class StringLengthValidatorWithMinAndMaxMapper implements InputMapper
{
    public function __construct(private readonly InputMapperProvider $provider)
    {
    }

    /**
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): string
    {
        if (!is_string($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'string');
        }

        if (strlen($data) < 1) {
            throw MappingFailedException::incorrectValue($data, $path, 'string with at least 1 characters');
        }

        if (strlen($data) > 5) {
            throw MappingFailedException::incorrectValue($data, $path, 'string with at most 5 characters');
        }

        return $data;
    }
}
