<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Runtime;

use LogicException;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;

/**
 * @extends Optional<never>
 */
final class OptionalNone extends Optional
{

    protected function __construct(
        private readonly ?MapperContext $context,
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
        throw MappingFailedException::missingKey($this->context, $this->key);
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
