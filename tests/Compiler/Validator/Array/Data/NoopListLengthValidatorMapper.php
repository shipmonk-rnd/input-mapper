<?php declare (strict_types=1);

namespace ShipMonkTests\InputMapper\Compiler\Validator\Array\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Wrapper\ValidatedMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperContext;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use function array_is_list;
use function count;
use function is_array;

/**
 * Generated mapper by {@see ValidatedMapperCompiler}. Do not edit directly.
 *
 * @implements Mapper<list<mixed>>
 */
class NoopListLengthValidatorMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @return list<mixed>
     * @throws MappingFailedException
     */
    public function map(mixed $data, ?MapperContext $context = null): array
    {
        if (!is_array($data) || !array_is_list($data)) {
            throw MappingFailedException::incorrectType($data, $context, 'list');
        }

        $mapped = [];

        foreach ($data as $index => $item) {
            $mapped[] = $item;
        }

        return $mapped;
    }
}
