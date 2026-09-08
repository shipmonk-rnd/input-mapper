<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Codec\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Codec\InlineCodecInputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use function is_string;

/**
 * Generated mapper by {@see InlineCodecInputMapperCompiler}. Do not edit directly.
 *
 * @implements Mapper<mixed, string>
 */
class InlineCodecCurrencyPrefixMapper implements Mapper
{
    private ?CurrencyPrefixCodec $codec = null;

    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): string
    {
        if (!is_string($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'string');
        }

        $this->codec ??= new CurrencyPrefixCodec(prefix: 'USD', separator: ':');
        return $this->codec->decode($data, $path);
    }
}
