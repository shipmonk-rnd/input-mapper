<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Runtime\Data;

use ShipMonk\InputMapper\Runtime\Codec;
use ShipMonk\InputMapper\Runtime\CodecRegistry;

/**
 * @template T of TypedId
 * @implements Codec<string, T, T, string>
 */
class TypedIdCodec implements Codec
{

    /**
     * @param class-string<T> $className
     */
    public function __construct(
        private readonly string $className,
    )
    {
    }

    /**
     * @param class-string $className
     * @return self<TypedId>
     */
    public static function create(string $className, CodecRegistry $registry): self
    {
        return new self($className); // @phpstan-ignore argument.type
    }

    /**
     * @param string $data
     * @param list<string|int> $path
     * @return T
     */
    public function decode(mixed $data, array $path = []): TypedId
    {
        return new ($this->className)($data);
    }

    /**
     * @param T $data
     * @param list<string|int> $path
     */
    public function encode(mixed $data, array $path = []): string
    {
        return $data->toString();
    }

}
