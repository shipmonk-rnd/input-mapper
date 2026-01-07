<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\MapperFactory\Data;

/**
 * @template T
 */
class EqualsFilterInput
{

    /**
     * @param T $equals
     */
    public function __construct(
        public readonly mixed $equals,
    )
    {
    }

}
