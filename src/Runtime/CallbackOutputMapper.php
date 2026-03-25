<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Runtime;

use Closure;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;

/**
 * @template-contravariant T
 * @implements OutputMapper<T>
 */
class CallbackOutputMapper implements OutputMapper
{

    /**
     * @param Closure(T, list<string|int>): mixed $callback
     */
    public function __construct(
        private readonly Closure $callback,
    )
    {
    }

    /**
     * @param T $data
     * @param list<string|int> $path
     *
     * @throws MappingFailedException
     */
    public function map(
        mixed $data,
        array $path = [],
    ): mixed
    {
        /** @throws MappingFailedException */
        return ($this->callback)($data, $path);
    }

}
