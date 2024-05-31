<?php declare (strict_types=1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Wrapper\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Object\MapObject;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use function array_column;
use function array_diff_key;
use function array_key_exists;
use function array_keys;
use function count;
use function implode;
use function is_array;
use function is_string;

/**
 * Generated mapper by {@see MapObject}. Do not edit directly.
 *
 * @implements Mapper<Semaphore>
 */
class SemaphoreMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): Semaphore
    {
        if (!is_array($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'array');
        }

        $knownKeys = ['color' => true, 'manufacturer' => true];
        $extraKeys = array_diff_key($data, $knownKeys);

        if (count($extraKeys) > 0) {
            throw MappingFailedException::extraKeys($path, array_keys($extraKeys));
        }

        return new Semaphore(
            array_key_exists('color', $data) ? $this->mapColor($data['color'], [...$path, 'color']) : SemaphoreColorEnum::Green,
            array_key_exists('manufacturer', $data) ? $this->mapManufacturer($data['manufacturer'], [...$path, 'manufacturer']) : null,
        );
    }

    /**
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    private function mapColor(mixed $data, array $path = []): SemaphoreColorEnum
    {
        if (!is_string($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'string');
        }

        $enum = SemaphoreColorEnum::tryFrom($data);

        if ($enum === null) {
            throw MappingFailedException::incorrectValue($data, $path, 'one of ' . implode(', ', array_column(SemaphoreColorEnum::cases(), 'value')));
        }

        return $enum;
    }

    /**
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    private function mapManufacturer(mixed $data, array $path = []): ?string
    {
        if ($data === null) {
            $mapped = null;
        } else {
            if (!is_string($data)) {
                throw MappingFailedException::incorrectType($data, $path, 'string');
            }

            $mapped = $data;
        }

        return $mapped;
    }
}
