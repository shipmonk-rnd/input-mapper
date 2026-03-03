<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Array\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Output\ArrayShapeOutputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\OutputMapper;
use ShipMonk\InputMapper\Runtime\OutputMapperProvider;
use function array_key_exists;

/**
 * Generated mapper by {@see ArrayShapeOutputMapperCompiler}. Do not edit directly.
 *
 * @implements OutputMapper<array{a: int, b?: string}>
 */
class SealedArrayShapeOutputMapper implements OutputMapper
{
    public function __construct(private readonly OutputMapperProvider $provider)
    {
    }

    /**
     * @param  array{a: int, b?: string} $data
     * @param  list<string|int> $path
     * @return array{a: int, b?: string}
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): mixed
    {
        $mapped = [];
        $mapped['a'] = $this->mapA($data['a'], [...$path, 'a']);

        if (array_key_exists('b', $data)) {
            $mapped['b'] = $this->mapB($data['b'], [...$path, 'b']);
        }

        return $mapped;
    }

    /**
     * @param  int $data
     * @param  list<string|int> $path
     * @return int
     * @throws MappingFailedException
     */
    private function mapA(mixed $data, array $path = []): mixed
    {
        return $data;
    }

    /**
     * @param  string $data
     * @param  list<string|int> $path
     * @return string
     * @throws MappingFailedException
     */
    private function mapB(mixed $data, array $path = []): mixed
    {
        return $data;
    }
}
