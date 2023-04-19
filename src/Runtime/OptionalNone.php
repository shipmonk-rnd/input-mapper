<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Runtime;

use LogicException;

/**
 * @extends Optional<never>
 */
final class OptionalNone extends Optional
{

    /**
     * @param  list<string|int> $path
     */
    protected function __construct(
        private readonly array $path,
        private readonly string $key,
    )
    {
    }

    public function isDefined(): bool
    {
        return false;
    }

    public function get(): never
    {
        throw new LogicException('Optional is not defined');
    }

    /**
     * @throws MappingFailedException
     */
    public function require(): never
    {
        throw MappingFailedException::missingKey($this->path, $this->key);
    }

    /**
     * @template D
     * @param  D $default
     * @return D
     */
    public function getOrElse(mixed $default): mixed
    {
        return $default;
    }

}
