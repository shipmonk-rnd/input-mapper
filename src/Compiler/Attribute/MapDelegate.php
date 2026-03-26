<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Attribute;

use Attribute;
use ShipMonk\InputMapper\Compiler\Mapper\Input\DelegateInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompilerProvider;
use ShipMonk\InputMapper\Compiler\Mapper\Output\DelegateOutputMapperCompiler;
use function array_map;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class MapDelegate implements MapperCompilerProvider
{

    /**
     * @param string $className class name or generic parameter name
     * @param list<MapperCompilerProvider> $innerMapperCompilerProviders
     */
    public function __construct(
        public readonly string $className,
        public readonly array $innerMapperCompilerProviders = [],
    )
    {
    }

    public function getInputMapperCompiler(): MapperCompiler
    {
        return new DelegateInputMapperCompiler(
            $this->className,
            array_map(static fn (MapperCompilerProvider $p): MapperCompiler => $p->getInputMapperCompiler(), $this->innerMapperCompilerProviders),
        );
    }

    public function getOutputMapperCompiler(): MapperCompiler
    {
        return new DelegateOutputMapperCompiler(
            $this->className,
            array_map(static fn (MapperCompilerProvider $p): MapperCompiler => $p->getOutputMapperCompiler(), $this->innerMapperCompilerProviders),
        );
    }

}
