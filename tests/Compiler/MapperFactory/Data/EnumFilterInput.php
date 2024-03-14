<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\MapperFactory\Data;

use BackedEnum;

/**
 * @template T of BackedEnum
 */
class EnumFilterInput
{

    /**
     * @param  list<T> $in
     * @param  T       $color
     */
    public function __construct(
        public readonly array $in,
        public readonly BackedEnum $color,
    )
    {
    }

}
