<?php declare (strict_types=1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Object\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Object\MapDiscriminatedObject;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use function array_key_exists;
use function implode;
use function is_array;

/**
 * Generated mapper by {@see MapDiscriminatedObject}. Do not edit directly.
 *
 * @implements Mapper<HierarchicalParentInput>
 */
class HierarchicalParentInputMapper implements Mapper
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
            'childOne' => $this->mapChildOne($data, $path),
            'childTwo' => $this->mapChildTwo($data, $path),
            default => throw MappingFailedException::incorrectValue($data['type'], [...$path, 'type'], 'one of ' . implode(', ', ['childOne', 'childTwo'])),
        };
    }

    /**
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    private function mapChildOne(mixed $data, array $path = []): HierarchicalChildOneInput
    {
        return $this->provider->get(HierarchicalChildOneInput::class)->map($data, $path);
    }

    /**
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    private function mapChildTwo(mixed $data, array $path = []): HierarchicalChildTwoInput
    {
        return $this->provider->get(HierarchicalChildTwoInput::class)->map($data, $path);
    }
}
