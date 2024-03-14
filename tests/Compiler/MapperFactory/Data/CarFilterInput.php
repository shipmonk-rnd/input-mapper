<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\MapperFactory\Data;

class CarFilterInput
{

    /**
     * @param  InFilterInput<int>           $id
     * @param  EqualsFilterInput<ColorEnum> $color
     */
    public function __construct(
        public readonly InFilterInput $id,
        public readonly EqualsFilterInput $color,
    )
    {
    }

}
