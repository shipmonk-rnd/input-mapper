<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Runtime;

use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;

/**
 * @template-covariant  T
 */
interface Mapper
{

    /**
     * @return T
     * @throws MappingFailedException
     */
    public function map(mixed $data, ?MapperContext $context = null): mixed;

}
