<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Codec\Data;

use ShipMonk\InputMapperTests\Runtime\Data\MoneyCodec;
use ShipMonk\InputMapperTests\Runtime\Data\MoneyValue;
use ShipMonk\InputMapper\Compiler\Mapper\Codec\CodecInputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use function array_diff_key;
use function array_key_exists;
use function array_keys;
use function count;
use function is_array;
use function is_int;
use function is_string;

/**
 * Generated mapper by {@see CodecInputMapperCompiler}. Do not edit directly.
 *
 * @implements Mapper<mixed, MoneyValue>
 */
class CodecMoneyMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): MoneyValue
    {
        if (!is_array($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'array');
        }

        $mapped = [];

        if (!array_key_exists('currency', $data)) {
            throw MappingFailedException::missingKey($path, 'currency');
        }

        $mapped['currency'] = $this->mapCurrency($data['currency'], [...$path, 'currency']);

        if (!array_key_exists('cents', $data)) {
            throw MappingFailedException::missingKey($path, 'cents');
        }

        $mapped['cents'] = $this->mapCents($data['cents'], [...$path, 'cents']);
        $knownKeys = ['currency' => true, 'cents' => true];
        $extraKeys = array_diff_key($data, $knownKeys);

        if (count($extraKeys) > 0) {
            throw MappingFailedException::extraKeys($path, array_keys($extraKeys));
        }

        return $this->provider->getCodec(MoneyCodec::class, MoneyValue::class)->decode($mapped, $path);
    }

    /**
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    private function mapCurrency(mixed $data, array $path = []): string
    {
        if (!is_string($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'string');
        }

        return $data;
    }

    /**
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    private function mapCents(mixed $data, array $path = []): int
    {
        if (!is_int($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'int');
        }

        return $data;
    }
}
