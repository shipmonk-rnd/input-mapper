<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Object\Data;

use BackedEnum;

/**
 * @template T of BackedEnum
 */
class EnumCollectionInput
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
