<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Runtime;

/**
 * @template-covariant T
 */
abstract class Optional
{

    /**
     * @return OptionalNone
     */
    public static function none(): self
    {
        return new OptionalNone();
    }

    /**
     * @template R
     * @param  R $value
     * @return OptionalSome<R>
     */
    public static function of(mixed $value): self
    {
        return new OptionalSome($value);
    }

    abstract public function isDefined(): bool;

    /**
     * @return T
     */
    abstract public function get(): mixed;

    /**
     * @template D of T
     * @param  D $default
     * @return T|D
     */
    abstract public function getOrElse(mixed $default): mixed;

}
