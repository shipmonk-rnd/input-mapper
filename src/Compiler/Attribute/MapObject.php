<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Attribute;

use ShipMonk\InputMapper\Compiler\Mapper\Input\ObjectInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\InputMapperCompilerProvider;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompilerProvider;
use ShipMonk\InputMapper\Compiler\Mapper\Output\ObjectOutputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\OutputMapperCompilerProvider;
use ShipMonk\InputMapper\Compiler\Type\GenericTypeParameter;
use function array_map;

class MapObject implements MapperCompilerProvider
{

    /**
     * @param class-string<object> $className
     * @param array<string, InputMapperCompilerProvider> $constructorArgsProviders
     * @param array<string, array{string, OutputMapperCompilerProvider}> $propertyProviders propertyName => [outputKey, OutputMapperCompilerProvider]
     * @param list<GenericTypeParameter> $genericParameters
     */
    public function __construct(
        public readonly string $className,
        public readonly array $constructorArgsProviders,
        public readonly bool $allowExtraKeys,
        public readonly array $propertyProviders,
        public readonly array $genericParameters = [],
    )
    {
    }

    public function getInputMapperCompiler(): MapperCompiler
    {
        return new ObjectInputMapperCompiler(
            $this->className,
            array_map(
                static fn (InputMapperCompilerProvider $provider): MapperCompiler => $provider->getInputMapperCompiler(),
                $this->constructorArgsProviders,
            ),
            $this->allowExtraKeys,
            $this->genericParameters,
        );
    }

    public function getOutputMapperCompiler(): MapperCompiler
    {
        return new ObjectOutputMapperCompiler(
            $this->className,
            array_map(
                static fn (array $pair): array => [$pair[0], $pair[1]->getOutputMapperCompiler()],
                $this->propertyProviders,
            ),
            $this->genericParameters,
        );
    }

}
