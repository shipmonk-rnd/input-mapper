<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Runtime;

use Closure;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;

/**
 * @template-contravariant I
 * @template-covariant O
 * @implements Mapper<I, O>
 */
class CallbackMapper implements Mapper
{

    /**
     * @param Closure(I, list<string|int>): O $callback
     */
    public function __construct(
        private readonly Closure $callback,
    )
    {
    }

    /**
     * @param I $data
     * @param list<string|int> $path
     * @return O
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
