<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\MapperFactory\Data;

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
