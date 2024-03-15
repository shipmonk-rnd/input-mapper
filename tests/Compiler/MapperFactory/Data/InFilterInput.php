<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\MapperFactory\Data;

/**
 * @template T
 */
class InFilterInput
{

    /**
     * @param list<T> $in
     */
    public function __construct(
        public readonly mixed $in,
    )
    {
    }

}
