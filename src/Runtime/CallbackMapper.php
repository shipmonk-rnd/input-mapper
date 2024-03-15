<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Runtime;

use Closure;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;

/**
 * @template-covariant  T
 * @implements Mapper<T>
 */
class CallbackMapper implements Mapper
{

    /**
     * @param Closure(mixed, list<string|int>): T $callback
     */
    public function __construct(
        private readonly Closure $callback,
    )
    {
    }

    /**
     * @param  list<string|int>  $path
     * @return T
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): mixed
    {
        /** @throws MappingFailedException */
        return ($this->callback)($data, $path);
    }

}
