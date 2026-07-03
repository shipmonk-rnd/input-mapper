<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Attribute;

use Attribute;
use ShipMonk\InputMapper\Compiler\Mapper\Input\DefaultValueInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompilerProvider;
use ShipMonk\InputMapper\Compiler\MapperFactory\MapperCompilerFactory;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class MapDefaultValue implements MapperCompilerProvider
{

    public function __construct(
        public readonly MapperCompilerProvider $mapperCompilerProvider,
        public readonly mixed $defaultValue,
    )
    {
    }

    public function getInputMapperCompiler(
        MapperCompilerFactory $mapperCompilerFactory,
        array $options,
    ): MapperCompiler
    {
        return new DefaultValueInputMapperCompiler($this->mapperCompilerProvider->getInputMapperCompiler($mapperCompilerFactory, $options), $this->defaultValue);
    }

    public function getOutputMapperCompiler(
        MapperCompilerFactory $mapperCompilerFactory,
        array $options,
    ): MapperCompiler
    {
        return $this->mapperCompilerProvider->getOutputMapperCompiler($mapperCompilerFactory, $options);
    }

}
