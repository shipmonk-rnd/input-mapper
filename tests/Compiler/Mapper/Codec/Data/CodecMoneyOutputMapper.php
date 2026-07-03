<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Codec\Data;

use ShipMonk\InputMapperTests\Runtime\Data\MoneyCodec;
use ShipMonk\InputMapperTests\Runtime\Data\MoneyValue;
use ShipMonk\InputMapper\Compiler\Mapper\Codec\CodecOutputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;

/**
 * Generated mapper by {@see CodecOutputMapperCompiler}. Do not edit directly.
 *
 * @implements Mapper<MoneyValue, array{currency: string, cents: int}>
 */
class CodecMoneyOutputMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @param  MoneyValue $data
     * @param  list<string|int> $path
     * @return array{currency: string, cents: int}
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): array
    {
        $encoded = $this->provider->getCodec(MoneyCodec::class, MoneyValue::class)->encode($data, $path);
        return ['currency' => $encoded['currency'], 'cents' => $encoded['cents']];
    }
}
