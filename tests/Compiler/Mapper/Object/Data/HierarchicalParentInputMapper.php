<?php declare (strict_types=1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Object\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Object\MapDiscriminatedObject;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use function array_key_exists;
use function implode;
use function in_array;
use function is_array;
use function is_string;

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

        if (!in_array($data['type'], ['childOne', 'childTwo'], true)) {
            throw MappingFailedException::incorrectValue($data['type'], [...$path, 'type'], 'one of ' . implode(', ', ['childOne', 'childTwo']));
        }

        return match ($this->mapType($data['type'], [...$path, 'type'])) {
            'childOne' => $this->provider->get(HierarchicalChildOneInput::class)->map($data, $path),
            'childTwo' => $this->provider->get(HierarchicalChildTwoInput::class)->map($data, $path),
        };
    }

    /**
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    private function mapType(mixed $data, array $path = []): string
    {
        if (!is_string($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'string');
        }

        return $data;
    }
}
