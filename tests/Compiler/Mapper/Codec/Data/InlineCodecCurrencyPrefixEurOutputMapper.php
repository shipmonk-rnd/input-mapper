<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Codec\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Codec\InlineCodecOutputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;

/**
 * Generated mapper by {@see InlineCodecOutputMapperCompiler}. Do not edit directly.
 *
 * @implements Mapper<string, string>
 */
class InlineCodecCurrencyPrefixEurOutputMapper implements Mapper
{
    private ?CurrencyPrefixCodec $codec = null;

    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @param  string $data
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): string
    {
        $this->codec ??= new CurrencyPrefixCodec(prefix: 'EUR', separator: '-');
        $encoded = $this->codec->encode($data, $path);
        return $encoded;
    }
}
