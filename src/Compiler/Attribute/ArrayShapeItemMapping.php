<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Attribute;

class ArrayShapeItemMapping
{

    public function __construct(
        public readonly string $key,
        public readonly MapperCompilerProvider $mapper,
        public readonly bool $optional = false,
    )
    {
    }

}
