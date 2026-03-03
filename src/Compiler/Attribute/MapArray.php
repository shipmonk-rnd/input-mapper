<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Attribute;

use Attribute;
use ShipMonk\InputMapper\Compiler\Mapper\Input\ArrayInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class MapArray implements InputMapperCompilerProvider
{

    public function __construct(
        public readonly MapperCompiler $keyMapperCompiler,
        public readonly MapperCompiler $valueMapperCompiler,
    )
    {
    }

    public function getInputMapperCompiler(): MapperCompiler
    {
        return new ArrayInputMapperCompiler($this->keyMapperCompiler, $this->valueMapperCompiler);
    }

}
