<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Codec\Data;

use Attribute;
use ShipMonk\InputMapper\Runtime\Codec;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use function str_ends_with;
use function strlen;
use function substr;

/**
 * Converts between a suffixed string (e.g. "100!") and the bare value ("100").
 *
 * @implements Codec<string, string, string, string>
 */
#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class SuffixCodec implements Codec
{

    public function __construct(
        public readonly string $suffix = '!',
    )
    {
    }

    /**
     * @param list<string|int> $path
     * @throws MappingFailedException
     */
    public function decode(mixed $data, array $path = []): string
    {
        if (!str_ends_with($data, $this->suffix)) {
            throw MappingFailedException::incorrectValue($data, $path, "string ending with '{$this->suffix}'");
        }

        return substr($data, 0, -strlen($this->suffix));
    }

    /**
     * @param list<string|int> $path
     */
    public function encode(mixed $data, array $path = []): string
    {
        return $data . $this->suffix;
    }

}
