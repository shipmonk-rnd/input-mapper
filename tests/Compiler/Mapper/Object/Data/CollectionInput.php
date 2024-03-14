<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Object\Data;

/**
 * @template T
 */
class CollectionInput
{

    /**
     * @param  list<T> $items
     */
    public function __construct(
        public readonly array $items,
        public readonly int $size,
    )
    {
    }

}
