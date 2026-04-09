<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Attribute;

use ShipMonk\InputMapper\Compiler\Mapper\Input\DiscriminatedObjectInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompilerProvider;
use ShipMonk\InputMapper\Compiler\Mapper\Output\DiscriminatedObjectOutputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Type\GenericTypeParameter;
use function array_map;

/**
 * @template T of object
 */
class MapDiscriminatedObject implements MapperCompilerProvider
{

    /**
     * @param class-string<T> $className
     * @param array<string, MapperCompilerProvider> $subtypeProviders
     * @param list<GenericTypeParameter> $genericParameters
     */
    public function __construct(
        public readonly string $className,
        public readonly string $discriminatorKeyName,
        public readonly array $subtypeProviders,
        public readonly array $genericParameters = [],
    )
    {
    }

    public function getInputMapperCompiler(): MapperCompiler
    {
        return new DiscriminatedObjectInputMapperCompiler(
            $this->className,
            $this->discriminatorKeyName,
            array_map(
                static fn (MapperCompilerProvider $provider): MapperCompiler => $provider->getInputMapperCompiler(),
                $this->subtypeProviders,
            ),
            $this->genericParameters,
        );
    }

    public function getOutputMapperCompiler(): MapperCompiler
    {
        return new DiscriminatedObjectOutputMapperCompiler(
            $this->className,
            array_map(
                static fn (MapperCompilerProvider $provider): MapperCompiler => $provider->getOutputMapperCompiler(),
                $this->subtypeProviders,
            ),
            $this->genericParameters,
        );
    }

}
