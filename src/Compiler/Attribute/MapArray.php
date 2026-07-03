<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Attribute;

use Attribute;
use ShipMonk\InputMapper\Compiler\Mapper\Input\ArrayInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompilerProvider;
use ShipMonk\InputMapper\Compiler\MapperFactory\MapperCompilerFactory;
use ShipMonk\InputMapper\Compiler\Mapper\Output\ArrayOutputMapperCompiler;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class MapArray implements MapperCompilerProvider
{

    public function __construct(
        public readonly MapperCompilerProvider $keyMapperCompilerProvider,
        public readonly MapperCompilerProvider $valueMapperCompilerProvider,
    )
    {
    }

    public function getInputMapperCompiler(
        MapperCompilerFactory $mapperCompilerFactory,
        array $options,
    ): MapperCompiler
    {
        return new ArrayInputMapperCompiler(
            $this->keyMapperCompilerProvider->getInputMapperCompiler($mapperCompilerFactory, $options),
            $this->valueMapperCompilerProvider->getInputMapperCompiler($mapperCompilerFactory, $options),
        );
    }

    public function getOutputMapperCompiler(
        MapperCompilerFactory $mapperCompilerFactory,
        array $options,
    ): MapperCompiler
    {
        return new ArrayOutputMapperCompiler(
            $this->keyMapperCompilerProvider->getOutputMapperCompiler($mapperCompilerFactory, $options),
            $this->valueMapperCompilerProvider->getOutputMapperCompiler($mapperCompilerFactory, $options),
        );
    }

}
