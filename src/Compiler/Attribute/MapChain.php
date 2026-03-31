<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Attribute;

use LogicException;
use ShipMonk\InputMapper\Compiler\Mapper\Input\ChainMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\InputMapperCompilerProvider;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompilerProvider;
use function array_map;

class MapChain implements MapperCompilerProvider
{

    /**
     * @param list<InputMapperCompilerProvider> $providers
     */
    public function __construct(
        public readonly array $providers,
    )
    {
    }

    public function getInputMapperCompiler(): MapperCompiler
    {
        return new ChainMapperCompiler(
            array_map(
                static fn (InputMapperCompilerProvider $provider): MapperCompiler => $provider->getInputMapperCompiler(),
                $this->providers,
            ),
        );
    }

    public function getOutputMapperCompiler(): MapperCompiler
    {
        throw new LogicException('MapChain does not support output mapping');
    }

}
