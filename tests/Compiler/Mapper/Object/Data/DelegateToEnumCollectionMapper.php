<?php declare (strict_types=1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Object\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Object\DelegateMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;

/**
 * Generated mapper by {@see DelegateMapperCompiler}. Do not edit directly.
 *
 * @implements Mapper<CollectionInput<SuitEnum>>
 */
class DelegateToEnumCollectionMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @param  list<string|int> $path
     * @return CollectionInput<SuitEnum>
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): CollectionInput
    {
        $innerMappers = [$this->provider->get(SuitEnum::class)];
        return $this->provider->get(CollectionInput::class, $innerMappers)->map($data, $path);
    }
}
