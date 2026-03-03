<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Validator\String\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Input\ValidatedInputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\InputMapper;
use ShipMonk\InputMapper\Runtime\InputMapperProvider;
use function is_string;
use function preg_match;

/**
 * Generated mapper by {@see ValidatedInputMapperCompiler}. Do not edit directly.
 *
 * @implements InputMapper<string>
 */
class PatternValidatorMapper implements InputMapper
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

        if (preg_match('#^\\d+\\z#', $data) !== 1) {
            throw MappingFailedException::incorrectValue($data, $path, 'string matching pattern #^\\d+\\z#');
        }

        return $data;
    }
}
