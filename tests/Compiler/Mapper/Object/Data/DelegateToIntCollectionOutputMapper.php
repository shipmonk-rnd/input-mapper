<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Output\DelegateOutputMapperCompiler;
use ShipMonk\InputMapper\Runtime\CallbackOutputMapper;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\OutputMapper;
use ShipMonk\InputMapper\Runtime\OutputMapperProvider;

/**
 * Generated mapper by {@see DelegateOutputMapperCompiler}. Do not edit directly.
 *
 * @implements OutputMapper<CollectionInput<int>>
 */
class DelegateToIntCollectionOutputMapper implements OutputMapper
{
    public function __construct(private readonly OutputMapperProvider $provider)
    {
    }

    /**
     * @param  CollectionInput<int> $data
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): mixed
    {
        $innerMappers = [new CallbackOutputMapper($this->mapInner0(...))];
        return $this->provider->get(CollectionInput::class, $innerMappers)->map($data, $path);
    }

    /**
     * @param  int $data
     * @param  list<string|int> $path
     * @return int
     * @throws MappingFailedException
     */
    private function mapInner0(mixed $data, array $path = []): mixed
    {
        return $data;
    }
}
