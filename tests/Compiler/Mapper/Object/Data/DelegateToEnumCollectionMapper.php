<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Input\DelegateInputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\InputMapper;
use ShipMonk\InputMapper\Runtime\InputMapperProvider;

/**
 * Generated mapper by {@see DelegateInputMapperCompiler}. Do not edit directly.
 *
 * @implements InputMapper<CollectionInput<SuitEnum>>
 */
class DelegateToEnumCollectionMapper implements InputMapper
{
    public function __construct(private readonly InputMapperProvider $provider)
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
