<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Runtime;

use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;

/**
 * @template-covariant T
 */
abstract class Optional
{

    public static function none(?MapperContext $context, string $key): OptionalNone
    {
        return new OptionalNone($context, $key);
    }

    /**
     * @template R
     * @param  R $value
     * @return OptionalSome<R>
     */
    public static function of(mixed $value): OptionalSome
    {
        return new OptionalSome($value);
    }

    abstract public function isDefined(): bool;

    /**
     * @return T
     */
    abstract public function get(): mixed;

    /**
     * @return T
     * @throws MappingFailedException
     */
    abstract public function require(): mixed;

    /**
     * @template D
     * @param  D $default
     * @return T|D
     */
    abstract public function getOrElse(mixed $default): mixed;

}
