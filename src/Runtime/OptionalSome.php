<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Runtime;

/**
 * @template-covariant T
 * @extends Optional<T>
 */
final class OptionalSome extends Optional
{

    /**
     * @param  T $value
     */
    protected function __construct(
        private readonly mixed $value,
    )
    {
    }

    public function isDefined(): bool
    {
        return true;
    }

    /**
     * @return T
     */
    public function get(): mixed
    {
        return $this->value;
    }

    /**
     * @return T
     */
    public function require(): mixed
    {
        return $this->value;
    }

    /**
     * @template D
     * @param  D $default
     * @return T
     */
    public function getOrElse(mixed $default): mixed
    {
        return $this->value;
    }

}
