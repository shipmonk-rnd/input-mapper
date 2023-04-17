<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Runtime;

use LogicException;

/**
 * @extends Optional<never>
 */
final class OptionalNone extends Optional
{

    protected function __construct()
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
     * @template D
     * @param  D $default
     * @return D
     */
    public function getOrElse(mixed $default): mixed
    {
        return $default;
    }

}
