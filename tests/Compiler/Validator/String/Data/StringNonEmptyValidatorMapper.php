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
 * @implements InputMapper<non-empty-string>
 */
class StringNonEmptyValidatorMapper implements InputMapper
{
    public function __construct(private readonly InputMapperProvider $provider)
    {
    }

    /**
     * @param  list<string|int> $path
     * @return non-empty-string
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): string
    {
        if (!is_string($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'string');
        }

        if ($data === '' || preg_match('#\\S#', $data) !== 1) {
            throw MappingFailedException::incorrectValue($data, $path, 'non-empty string');
        }

        return $data;
    }
}
