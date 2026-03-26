<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Attribute;

use Attribute;
use ShipMonk\InputMapper\Compiler\Mapper\Input\ListInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompilerProvider;
use ShipMonk\InputMapper\Compiler\Mapper\Output\ListOutputMapperCompiler;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class MapList implements MapperCompilerProvider
{

    public function __construct(
        public readonly MapperCompilerProvider $itemMapperCompilerProvider,
    )
    {
    }

    public function getInputMapperCompiler(): MapperCompiler
    {
        return new ListInputMapperCompiler($this->itemMapperCompilerProvider->getInputMapperCompiler());
    }

    public function getOutputMapperCompiler(): MapperCompiler
    {
        return new ListOutputMapperCompiler($this->itemMapperCompilerProvider->getOutputMapperCompiler());
    }

}
