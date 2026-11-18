<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Wrapper\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Input\SensitiveInputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use function array_is_list;
use function is_array;
use function is_string;

/**
 * Generated mapper by {@see SensitiveInputMapperCompiler}. Do not edit directly.
 *
 * @implements Mapper<mixed, list<string>>
 */
class SensitiveStringListMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @param  list<string|int> $path
     * @return list<string>
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): array
    {
        try {
            if (!is_array($data) || !array_is_list($data)) {
                throw MappingFailedException::incorrectType($data, $path, 'list');
            }

            $mapped = [];

            foreach ($data as $index => $item) {
                if (!is_string($item)) {
                    throw MappingFailedException::incorrectType($item, [...$path, $index], 'string');
                }

                $mapped[] = $item;
            }

            $mapped2 = $mapped;
        } catch (MappingFailedException $e) {
            throw MappingFailedException::redact($e);
        }
        return $mapped2;
    }
}
