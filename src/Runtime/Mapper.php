<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Runtime;

use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;

/**
 * @template-covariant  T
 */
interface Mapper
{

    /**
     * @param  list<string|int> $path
     * @return T
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): mixed;

}
