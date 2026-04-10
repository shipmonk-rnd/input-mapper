<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Output\DelegateOutputMapperCompiler;
use ShipMonk\InputMapper\Runtime\CallbackMapper;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;

/**
 * Generated mapper by {@see DelegateOutputMapperCompiler}. Do not edit directly.
 *
 * @implements Mapper<CollectionInput<int>, mixed>
 */
class DelegateToIntCollectionOutputMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @param  CollectionInput<int> $data
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): mixed
    {
        $genericInnerMappers = [new CallbackMapper($this->mapInner0(...))];
        return $this->provider->getOutputMapper(CollectionInput::class, $genericInnerMappers)->map($data, $path);
    }

    /**
     * @param  int $data
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    private function mapInner0(mixed $data, array $path = []): int
    {
        return $data;
    }
}
