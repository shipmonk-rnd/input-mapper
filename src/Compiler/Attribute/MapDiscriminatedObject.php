<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Attribute;

use Attribute;
use ShipMonk\InputMapper\Compiler\Mapper\Input\DiscriminatedObjectInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Type\GenericTypeParameter;

/**
 * @template T of object
 */
#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class MapDiscriminatedObject implements InputMapperCompilerProvider
{

    /**
     * @param class-string<T> $className
     * @param array<string, MapperCompiler> $subtypeCompilers
     * @param list<GenericTypeParameter> $genericParameters
     */
    public function __construct(
        public readonly string $className,
        public readonly string $discriminatorKeyName,
        public readonly array $subtypeCompilers,
        public readonly array $genericParameters = [],
    )
    {
    }

    public function getInputMapperCompiler(): MapperCompiler
    {
        return new DiscriminatedObjectInputMapperCompiler($this->className, $this->discriminatorKeyName, $this->subtypeCompilers, $this->genericParameters);
    }

}
