<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Wrapper\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Input\ObjectInputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use function array_diff_key;
use function array_key_exists;
use function array_keys;
use function count;
use function is_array;

/**
 * Generated mapper by {@see ObjectInputMapperCompiler}. Do not edit directly.
 *
 * @implements Mapper<mixed, SemaphoreWithMode>
 */
class SemaphoreWithModeMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): SemaphoreWithMode
    {
        if (!is_array($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'array');
        }

        $knownKeys = ['mode' => true];
        $extraKeys = array_diff_key($data, $knownKeys);

        if (count($extraKeys) > 0) {
            throw MappingFailedException::extraKeys($path, array_keys($extraKeys));
        }

        return new SemaphoreWithMode(array_key_exists('mode', $data) ? $this->mapMode($data['mode'], [...$path, 'mode']) : SemaphoreModeEnum::Normal);
    }

    /**
     * @param  string $data
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    private function mapMode(mixed $data, array $path = []): SemaphoreModeEnum
    {
        return MapSemaphoreMode::mapValue($data, $path);
    }
}
