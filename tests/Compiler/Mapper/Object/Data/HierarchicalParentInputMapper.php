<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Input\DiscriminatedObjectInputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\InputMapper;
use ShipMonk\InputMapper\Runtime\InputMapperProvider;
use function array_key_exists;
use function implode;
use function is_array;

/**
 * Generated mapper by {@see DiscriminatedObjectInputMapperCompiler}. Do not edit directly.
 *
 * @implements InputMapper<HierarchicalParentInput>
 */
class HierarchicalParentInputMapper implements InputMapper
{
    public function __construct(private readonly InputMapperProvider $provider)
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
