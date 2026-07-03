<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Attribute;

use ShipMonk\InputMapper\Compiler\Mapper\Input\ChainMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompilerProvider;
use ShipMonk\InputMapper\Compiler\MapperFactory\MapperCompilerFactory;
use function array_map;
use function array_reverse;

class MapChain implements MapperCompilerProvider
{

    /**
     * @param list<MapperCompilerProvider> $providers
     */
    public function __construct(
        public readonly array $providers,
    )
    {
    }

    public function getInputMapperCompiler(
        MapperCompilerFactory $mapperCompilerFactory,
        array $options,
    ): MapperCompiler
    {
        return new ChainMapperCompiler(
            array_map(
                static fn (MapperCompilerProvider $provider): MapperCompiler => $provider->getInputMapperCompiler($mapperCompilerFactory, $options),
                $this->providers,
            ),
        );
    }

    public function getOutputMapperCompiler(
        MapperCompilerFactory $mapperCompilerFactory,
        array $options,
    ): MapperCompiler
    {
        return new ChainMapperCompiler(
            array_map(
                static fn (MapperCompilerProvider $provider): MapperCompiler => $provider->getOutputMapperCompiler($mapperCompilerFactory, $options),
                array_reverse($this->providers),
            ),
        );
    }

}
