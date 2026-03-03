<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Attribute;

use Attribute;
use ShipMonk\InputMapper\Compiler\Mapper\Input\ArrayShapeInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class MapArrayShape implements InputMapperCompilerProvider
{

    /**
     * @param list<ArrayShapeItemMapping> $items
     */
    public function __construct(
        public readonly array $items,
        public readonly bool $sealed = true,
    )
    {
    }

    public function getInputMapperCompiler(): MapperCompiler
    {
        return new ArrayShapeInputMapperCompiler($this->items, $this->sealed);
    }

}
