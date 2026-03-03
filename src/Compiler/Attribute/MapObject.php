<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Attribute;

use Attribute;
use ShipMonk\InputMapper\Compiler\Mapper\Input\ObjectInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Type\GenericTypeParameter;

/**
 * @template T of object
 */
#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class MapObject implements InputMapperCompilerProvider
{

    /**
     * @param class-string<T> $className
     * @param array<string, MapperCompiler> $constructorArgsMapperCompilers
     * @param list<GenericTypeParameter> $genericParameters
     */
    public function __construct(
        public readonly string $className,
        public readonly array $constructorArgsMapperCompilers,
        public readonly bool $allowExtraKeys = false,
        public readonly array $genericParameters = [],
    )
    {
    }

    public function getInputMapperCompiler(): MapperCompiler
    {
        return new ObjectInputMapperCompiler($this->className, $this->constructorArgsMapperCompilers, $this->allowExtraKeys, $this->genericParameters);
    }

}
