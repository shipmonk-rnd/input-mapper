<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Output\DelegateOutputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\OutputMapper;
use ShipMonk\InputMapper\Runtime\OutputMapperProvider;

/**
 * Generated mapper by {@see DelegateOutputMapperCompiler}. Do not edit directly.
 *
 * @implements OutputMapper<CollectionInput<SuitEnum>>
 */
class DelegateToEnumCollectionOutputMapper implements OutputMapper
{
    public function __construct(private readonly OutputMapperProvider $provider)
    {
    }

    /**
     * @param  CollectionInput<SuitEnum> $data
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): mixed
    {
        $innerMappers = [$this->provider->get(SuitEnum::class)];
        return $this->provider->get(CollectionInput::class, $innerMappers)->map($data, $path);
    }
}
