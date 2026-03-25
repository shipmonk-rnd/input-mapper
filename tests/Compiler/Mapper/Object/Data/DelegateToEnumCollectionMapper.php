<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Input\DelegateInputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;

/**
 * Generated mapper by {@see DelegateInputMapperCompiler}. Do not edit directly.
 *
 * @implements Mapper<mixed, CollectionInput<SuitEnum>>
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
        $innerMappers = [$this->provider->getInputMapper(SuitEnum::class)];
        return $this->provider->getInputMapper(CollectionInput::class, $innerMappers)->map($data, $path);
    }
}
