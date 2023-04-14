<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Runtime;

/**
 * @template-covariant  T
 */
interface Mapper
{

    /**
     * @param  list<string|int> $path
     * @return T
     */
    public function map(mixed $data, array $path = []): mixed;

}
