<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Attribute;

use Attribute;
use BackedEnum;
use ShipMonk\InputMapper\Compiler\Mapper\Input\EnumInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\InputMapperCompilerProvider;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompilerProvider;
use ShipMonk\InputMapper\Compiler\MapperFactory\MapperCompilerFactory;
use ShipMonk\InputMapper\Compiler\Mapper\Output\EnumOutputMapperCompiler;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class MapEnum implements MapperCompilerProvider
{

    /**
     * @param class-string<BackedEnum> $enumName
     */
    public function __construct(
        public readonly string $enumName,
        public readonly InputMapperCompilerProvider $backingValueMapperCompilerProvider,
    )
    {
    }

    public function getInputMapperCompiler(
        MapperCompilerFactory $mapperCompilerFactory,
        array $options,
    ): MapperCompiler
    {
        return new EnumInputMapperCompiler($this->enumName, $this->backingValueMapperCompilerProvider->getInputMapperCompiler($mapperCompilerFactory, $options));
    }

    public function getOutputMapperCompiler(
        MapperCompilerFactory $mapperCompilerFactory,
        array $options,
    ): MapperCompiler
    {
        return new EnumOutputMapperCompiler($this->enumName);
    }

}
