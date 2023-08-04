<?php declare (strict_types=1);

namespace ShipMonkTests\InputMapper\Compiler\Validator\String\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Wrapper\ValidatedMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use function is_string;
use function strlen;

/**
 * Generated mapper by {@see ValidatedMapperCompiler}. Do not edit directly.
 *
 * @implements Mapper<string>
 */
class StringLengthValidatorWithMaxMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
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

        if (strlen($data) > 5) {
            throw MappingFailedException::incorrectValue($data, $path, 'string with at most 5 characters');
        }

        return $data;
    }
}
