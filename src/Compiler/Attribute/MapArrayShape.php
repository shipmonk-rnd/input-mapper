<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Attribute;

use Attribute;
use ShipMonk\InputMapper\Compiler\Mapper\Input\ArrayShapeInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Output\ArrayShapeOutputMapperCompiler;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class MapArrayShape implements InputMapperCompilerProvider, OutputMapperCompilerProvider
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

    public function getOutputMapperCompiler(): MapperCompiler
    {
        return new ArrayShapeOutputMapperCompiler($this->items, $this->sealed);
    }

}
