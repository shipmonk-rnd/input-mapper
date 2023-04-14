<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper\Object;

use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;

class PropertyMapping
{

    public function __construct(
        public readonly string $name,
        public readonly MapperCompiler $mapper,
        public readonly bool $optional = false,
    )
    {
    }

}
