<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Output\DiscriminatedObjectOutputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;

/**
 * Generated mapper by {@see DiscriminatedObjectOutputMapperCompiler}. Do not edit directly.
 *
 * @implements Mapper<HierarchicalParentInput, mixed>
 */
class HierarchicalParentOutputMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @param  HierarchicalParentInput $data
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): mixed
    {
        return match (true) {
            $data instanceof HierarchicalChildOneInput => $this->mapChildOne($data, $path),
            $data instanceof HierarchicalChildTwoInput => $this->mapChildTwo($data, $path),
            default => throw MappingFailedException::incorrectType($data, $path, 'ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data\HierarchicalParentInput'),
        };
    }

    /**
     * @param  HierarchicalChildOneInput $data
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    private function mapChildOne(mixed $data, array $path = []): mixed
    {
        return $this->provider->getOutputMapper(HierarchicalChildOneInput::class)->map($data, $path);
    }

    /**
     * @param  HierarchicalChildTwoInput $data
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    private function mapChildTwo(mixed $data, array $path = []): mixed
    {
        return $this->provider->getOutputMapper(HierarchicalChildTwoInput::class)->map($data, $path);
    }
}
