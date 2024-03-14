<?php declare (strict_types=1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Object\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Object\DelegateMapperCompiler;
use ShipMonk\InputMapper\Runtime\CallbackMapper;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use function is_int;

/**
 * Generated mapper by {@see DelegateMapperCompiler}. Do not edit directly.
 *
 * @implements Mapper<CollectionInput<int>>
 */
class DelegateToIntCollectionMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @param  list<string|int> $path
     * @return CollectionInput<int>
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): CollectionInput
    {
        $innerMappers = [new CallbackMapper($this->mapInner0(...))];
        return $this->provider->get(CollectionInput::class, $innerMappers)->map($data, $path);
    }

    /**
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    private function mapInner0(mixed $data, array $path = []): int
    {
        if (!is_int($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'int');
        }

        return $data;
    }
}
