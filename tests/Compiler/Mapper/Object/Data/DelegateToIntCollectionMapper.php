<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Input\DelegateInputMapperCompiler;
use ShipMonk\InputMapper\Runtime\CallbackInputMapper;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\InputMapper;
use ShipMonk\InputMapper\Runtime\InputMapperProvider;
use function is_int;

/**
 * Generated mapper by {@see DelegateInputMapperCompiler}. Do not edit directly.
 *
 * @implements InputMapper<CollectionInput<int>>
 */
class DelegateToIntCollectionMapper implements InputMapper
{
    public function __construct(private readonly InputMapperProvider $provider)
    {
    }

    /**
     * @param  list<string|int> $path
     * @return CollectionInput<int>
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): CollectionInput
    {
        $innerMappers = [new CallbackInputMapper($this->mapInner0(...))];
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
