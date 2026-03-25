<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Runtime;

use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;

/**
 * @template-contravariant I
 * @template-covariant O
 */
interface Mapper
{

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
    ): mixed;

}
