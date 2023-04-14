<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper\Array;

use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;

class ArrayShapeItemMapping
{

    public function __construct(
        public readonly string $key,
        public readonly MapperCompiler $mapper,
        public readonly bool $optional = false,
    )
    {
    }

}
