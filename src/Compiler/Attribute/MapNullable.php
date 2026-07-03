<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Attribute;

use Attribute;
use ShipMonk\InputMapper\Compiler\Mapper\Input\NullableInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompilerProvider;
use ShipMonk\InputMapper\Compiler\MapperFactory\MapperCompilerFactory;
use ShipMonk\InputMapper\Compiler\Mapper\Output\NullableOutputMapperCompiler;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class MapNullable implements MapperCompilerProvider
{

    public function __construct(
        public readonly MapperCompilerProvider $innerMapperCompilerProvider,
    )
    {
    }

    public function getInputMapperCompiler(
        MapperCompilerFactory $mapperCompilerFactory,
        array $options,
    ): MapperCompiler
    {
        return new NullableInputMapperCompiler($this->innerMapperCompilerProvider->getInputMapperCompiler($mapperCompilerFactory, $options));
    }

    public function getOutputMapperCompiler(
        MapperCompilerFactory $mapperCompilerFactory,
        array $options,
    ): MapperCompiler
    {
        return new NullableOutputMapperCompiler($this->innerMapperCompilerProvider->getOutputMapperCompiler($mapperCompilerFactory, $options));
    }

}
