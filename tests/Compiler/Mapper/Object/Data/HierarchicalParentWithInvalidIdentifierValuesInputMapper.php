<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Input\DiscriminatedObjectInputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use function array_key_exists;
use function implode;
use function is_array;

/**
 * Generated mapper by {@see DiscriminatedObjectInputMapperCompiler}. Do not edit directly.
 *
 * @implements Mapper<mixed, HierarchicalParentInput>
 */
class HierarchicalParentWithInvalidIdentifierValuesInputMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): HierarchicalParentInput
    {
        if (!is_array($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'array');
        }

        if (!array_key_exists('type', $data)) {
            throw MappingFailedException::missingKey($path, 'type');
        }

        return match ($data['type']) {
            'child-one' => $this->mapChild_one($data, $path),
            'child:two' => $this->mapChild_two($data, $path),
            default => throw MappingFailedException::incorrectValue($data['type'], [...$path, 'type'], 'one of ' . implode(', ', ['child-one', 'child:two'])),
        };
    }

    /**
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    private function mapChild_one(mixed $data, array $path = []): HierarchicalChildOneInput
    {
        return $this->provider->getInputMapper(HierarchicalChildOneInput::class)->map($data, $path);
    }

    /**
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    private function mapChild_two(mixed $data, array $path = []): HierarchicalChildTwoInput
    {
        return $this->provider->getInputMapper(HierarchicalChildTwoInput::class)->map($data, $path);
    }
}
