<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Runtime\Data\Pregeneration;

class OrderInput
{

    /**
     * @param list<ItemInput> $items
     */
    public function __construct(
        public readonly CustomerInput $customer,
        public readonly array $items,
    )
    {
    }

}
