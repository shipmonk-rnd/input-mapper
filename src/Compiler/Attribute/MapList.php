<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Attribute;

use Attribute;
use ShipMonk\InputMapper\Compiler\Mapper\Input\ListInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Output\ListOutputMapperCompiler;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class MapList implements InputMapperCompilerProvider, OutputMapperCompilerProvider
{

    public function __construct(
        public readonly MapperCompiler $itemMapperCompiler,
    )
    {
    }

    public function getInputMapperCompiler(): MapperCompiler
    {
        return new ListInputMapperCompiler($this->itemMapperCompiler);
    }

    public function getOutputMapperCompiler(): MapperCompiler
    {
        return new ListOutputMapperCompiler($this->itemMapperCompiler);
    }

}
