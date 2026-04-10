<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Wrapper\Data;

use ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data\SuitEnum;
use ShipMonk\InputMapper\Compiler\Mapper\Output\NullableOutputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;

/**
 * Generated mapper by {@see NullableOutputMapperCompiler}. Do not edit directly.
 *
 * @implements Mapper<?list<SuitEnum>, ?list<string>>
 */
class NullableEnumListOutputMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @param  ?list<SuitEnum> $data
     * @param  list<string|int> $path
     * @return ?list<string>
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): ?array
    {
        if ($data === null) {
            $mapped2 = null;
        } else {
            $mapped = [];

            foreach ($data as $index => $item) {
                $mapped[] = $item->value;
            }

            $mapped2 = $mapped;
        }

        return $mapped2;
    }
}
