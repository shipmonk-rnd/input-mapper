<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Attribute;

use Attribute;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use ReflectionClass;
use ReflectionProperty;
use ShipMonk\InputMapper\Compiler\Exception\CannotCreateMapperCompilerException;
use ShipMonk\InputMapper\Compiler\Mapper\Codec\InlineCodecInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Codec\InlineCodecOutputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompilerProvider;
use ShipMonk\InputMapper\Compiler\MapperFactory\MapperCompilerFactory;
use ShipMonk\InputMapper\Compiler\Type\PhpDocTypeUtils;
use ShipMonk\InputMapper\Runtime\Codec;
use function get_debug_type;
use function is_scalar;

/**
 * Maps a value with the given codec instance, re-instantiated inside the generated mapper.
 *
 * The codec is re-created from its constructor arguments at compile time,
 * so all its constructor arguments must be scalar or null values readable from properties of the same name.
 */
#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class MapCodec implements MapperCompilerProvider
{

    /**
     * @param Codec<*, *, *, *> $codec
     */
    public function __construct(
        public readonly Codec $codec,
    )
    {
    }

    public function getInputMapperCompiler(
        MapperCompilerFactory $mapperCompilerFactory,
        array $options,
    ): MapperCompiler
    {
        $codecType = new IdentifierTypeNode($this->codec::class);
        $intermediateType = PhpDocTypeUtils::inferGenericParameter($codecType, Codec::class, 0);
        $domainType = PhpDocTypeUtils::inferGenericParameter($codecType, Codec::class, 2);
        $options[MapperCompilerFactory::DELEGATE_OBJECT_MAPPING] ??= true;

        return new InlineCodecInputMapperCompiler(
            $this->codec::class,
            $this->extractCodecConstructorArgs(),
            $mapperCompilerFactory->create($intermediateType, $options)->getInputMapperCompiler($mapperCompilerFactory, $options),
            $domainType,
        );
    }

    public function getOutputMapperCompiler(
        MapperCompilerFactory $mapperCompilerFactory,
        array $options,
    ): MapperCompiler
    {
        $codecType = new IdentifierTypeNode($this->codec::class);
        $domainType = PhpDocTypeUtils::inferGenericParameter($codecType, Codec::class, 1);
        $intermediateType = PhpDocTypeUtils::inferGenericParameter($codecType, Codec::class, 3);
        $options[MapperCompilerFactory::DELEGATE_OBJECT_MAPPING] ??= true;

        return new InlineCodecOutputMapperCompiler(
            $this->codec::class,
            $this->extractCodecConstructorArgs(),
            $mapperCompilerFactory->create($intermediateType, $options)->getOutputMapperCompiler($mapperCompilerFactory, $options),
            $domainType,
        );
    }

    /**
     * @return array<string, scalar|null>
     */
    protected function extractCodecConstructorArgs(): array
    {
        $args = [];
        $reflection = new ReflectionClass($this->codec);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return $args;
        }

        foreach ($constructor->getParameters() as $parameter) {
            $parameterName = $parameter->getName();
            $property = $this->findCodecProperty($reflection, $parameterName)
                ?? throw CannotCreateMapperCompilerException::withUnsupportedCodecConstructorArgument($this->codec::class, $parameterName, 'has no matching property');

            if ($property->isStatic()) {
                throw CannotCreateMapperCompilerException::withUnsupportedCodecConstructorArgument($this->codec::class, $parameterName, 'matches a static property');
            }

            if (!$property->isInitialized($this->codec)) {
                throw CannotCreateMapperCompilerException::withUnsupportedCodecConstructorArgument($this->codec::class, $parameterName, 'matches an uninitialized property');
            }

            $value = $property->getValue($this->codec);

            if ($value !== null && !is_scalar($value)) {
                throw CannotCreateMapperCompilerException::withUnsupportedCodecConstructorArgument($this->codec::class, $parameterName, 'must be a scalar or null value, got ' . get_debug_type($value));
            }

            $args[$parameterName] = $value;
        }

        return $args;
    }

    /**
     * Finds a property in the codec class hierarchy, including private properties of parent classes.
     *
     * @param ReflectionClass<T> $class
     *
     * @template T of object
     */
    private function findCodecProperty(
        ReflectionClass $class,
        string $name,
    ): ?ReflectionProperty
    {
        for ($current = $class; $current !== false; $current = $current->getParentClass()) {
            if ($current->hasProperty($name)) {
                return $current->getProperty($name);
            }
        }

        return null;
    }

}
