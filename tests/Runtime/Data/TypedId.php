<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Runtime\Data;

abstract class TypedId
{

    public function __construct(
        public readonly string $value,
    )
    {
    }

    public function toString(): string
    {
        return $this->value;
    }

}
