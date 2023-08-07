<?php declare (strict_types=1);

namespace ShipMonkTests\InputMapper\Compiler\Validator\String\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Wrapper\ValidatedMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use function is_string;
use function preg_match;

/**
 * Generated mapper by {@see ValidatedMapperCompiler}. Do not edit directly.
 *
 * @implements Mapper<string>
 */
class PatternValidatorMapper implements Mapper
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

        if (preg_match('#^\\d+\\z#', $data) !== 1) {
            throw MappingFailedException::incorrectValue($data, $path, 'string matching pattern #^\\d+\\z#');
        }

        return $data;
    }
}
