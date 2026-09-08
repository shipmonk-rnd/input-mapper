<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Codec\Data;

use Attribute;
use ShipMonk\InputMapper\Runtime\Codec;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use function str_starts_with;
use function strlen;
use function substr;

/**
 * A simple codec for testing inline codec attributes.
 * Converts between a prefixed string (e.g. "USD:1299") and the bare value ("1299").
 *
 * @implements Codec<string, string, string, string>
 */
#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class CurrencyPrefixCodec implements Codec
{

    public function __construct(
        public readonly string $prefix = 'USD',
        public readonly string $separator = ':',
    )
    {
    }

    /**
     * @param list<string|int> $path
     * @throws MappingFailedException
     */
    public function decode(mixed $data, array $path = []): string
    {
        $expectedPrefix = $this->prefix . $this->separator;

        if (!str_starts_with($data, $expectedPrefix)) {
            throw MappingFailedException::incorrectValue($data, $path, "string starting with '{$expectedPrefix}'");
        }

        return substr($data, strlen($expectedPrefix));
    }

    /**
     * @param list<string|int> $path
     */
    public function encode(mixed $data, array $path = []): string
    {
        return $this->prefix . $this->separator . $data;
    }

}
