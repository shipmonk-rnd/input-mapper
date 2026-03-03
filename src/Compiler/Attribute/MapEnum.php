<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Attribute;

use Attribute;
use BackedEnum;
use ShipMonk\InputMapper\Compiler\Mapper\Input\EnumInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class MapEnum implements InputMapperCompilerProvider
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

}
