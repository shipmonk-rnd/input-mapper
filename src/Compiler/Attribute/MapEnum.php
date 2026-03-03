<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Attribute;

use Attribute;
use BackedEnum;
use ShipMonk\InputMapper\Compiler\Mapper\Input\EnumInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Output\EnumOutputMapperCompiler;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class MapEnum implements InputMapperCompilerProvider, OutputMapperCompilerProvider
{

    /**
     * @param class-string<BackedEnum> $enumName
     */
    public function __construct(
        public readonly string $enumName,
        public readonly MapperCompiler $backingValueMapperCompiler,
    )
    {
    }

    public function getInputMapperCompiler(): MapperCompiler
    {
        return new EnumInputMapperCompiler($this->enumName, $this->backingValueMapperCompiler);
    }

    public function getOutputMapperCompiler(): MapperCompiler
    {
        return new EnumOutputMapperCompiler($this->enumName);
    }

}
