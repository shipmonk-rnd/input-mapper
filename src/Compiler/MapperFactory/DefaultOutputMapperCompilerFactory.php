<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\MapperFactory;

use BackedEnum;
use DateTimeImmutable;
use DateTimeInterface;
use LogicException;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprStringNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\NullableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use ShipMonk\InputMapper\Compiler\Attribute\OutputMapperCompilerProvider;
use ShipMonk\InputMapper\Compiler\Attribute\SourceKey;
use ShipMonk\InputMapper\Compiler\Exception\CannotCreateMapperCompilerException;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Output\ArrayOutputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Output\ArrayShapeOutputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Output\DateTimeImmutableOutputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Output\DelegateOutputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Output\EnumOutputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Output\ListOutputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Output\NullableOutputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Output\ObjectOutputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Output\OptionalOutputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\PassthroughMapperCompiler;
use ShipMonk\InputMapper\Compiler\Type\PhpDocTypeUtils;
use ShipMonk\InputMapper\Runtime\Optional;
use function array_column;
use function array_fill_keys;
use function class_exists;
use function class_implements;
use function class_parents;
use function count;
use function interface_exists;
use function is_array;
use function strtolower;
use function substr;

class DefaultOutputMapperCompilerFactory implements MapperCompilerFactory
{

    final public const DELEGATE_OBJECT_MAPPING = 'delegateObjectMapping';
    final public const GENERIC_PARAMETERS = 'genericParameters';

    /**
     * @param array<class-string, callable(class-string, array<string, mixed>): MapperCompiler> $mapperCompilerFactories
     */
    public function __construct(
        protected readonly Lexer $phpDocLexer,
        protected readonly PhpDocParser $phpDocParser,
        protected array $mapperCompilerFactories = [],
    )
    {
        $this->setMapperCompilerFactory(BackedEnum::class, $this->createEnumMapperCompiler(...));
        $this->setMapperCompilerFactory(DateTimeInterface::class, $this->createDateTimeMapperCompiler(...));
    }

    /**
     * @param class-string<T> $className
     * @param callable(class-string<T>, array<string, mixed>): MapperCompiler $factory
     *
     * @template T of object
     */
    public function setMapperCompilerFactory(
        string $className,
        callable $factory,
    ): void
    {
        $this->mapperCompilerFactories[$className] = $factory; // @phpstan-ignore-line
    }

    /**
     * @param array<string, mixed> $options
     */
    public function create(
        TypeNode $type,
        array $options = [],
    ): MapperCompiler
    {
        if ($type instanceof IdentifierTypeNode) {
            if (!PhpDocTypeUtils::isKeyword($type)) {
                if (isset($options[self::DELEGATE_OBJECT_MAPPING]) && $options[self::DELEGATE_OBJECT_MAPPING] === true) {
                    if (!class_exists($type->name) && !interface_exists($type->name)) {
                        if (!isset($options[self::GENERIC_PARAMETERS]) || !is_array($options[self::GENERIC_PARAMETERS]) || !isset($options[self::GENERIC_PARAMETERS][$type->name])) {
                            throw CannotCreateMapperCompilerException::fromType($type, 'there is no class, interface or enum with this name');
                        }
                    }

                    return new DelegateOutputMapperCompiler($type->name);
                }

                if (!class_exists($type->name) && !interface_exists($type->name)) {
                    throw CannotCreateMapperCompilerException::fromType($type, 'there is no class, interface or enum with this name');
                }

                return $this->createObjectMapperCompiler($type->name, $options);
            }

            return match (strtolower($type->name)) {
                'bool' => new PassthroughMapperCompiler(new IdentifierTypeNode('bool')),
                'float' => new PassthroughMapperCompiler(new IdentifierTypeNode('float')),
                'int' => new PassthroughMapperCompiler(new IdentifierTypeNode('int')),
                'mixed' => new PassthroughMapperCompiler(new IdentifierTypeNode('mixed')),
                'string' => new PassthroughMapperCompiler(new IdentifierTypeNode('string')),
                default => throw CannotCreateMapperCompilerException::fromType($type),
            };
        }

        if ($type instanceof NullableTypeNode) {
            return new NullableOutputMapperCompiler($this->createInner($type->type, $options));
        }

        if ($type instanceof GenericTypeNode) {
            return match (strtolower($type->type->name)) {
                'array' => match (count($type->genericTypes)) {
                    1 => new ArrayOutputMapperCompiler(new PassthroughMapperCompiler(new IdentifierTypeNode('mixed')), $this->createInner($type->genericTypes[0], $options)),
                    2 => new ArrayOutputMapperCompiler($this->createInner($type->genericTypes[0], $options), $this->createInner($type->genericTypes[1], $options)),
                    default => throw CannotCreateMapperCompilerException::fromType($type),
                },
                strtolower(Optional::class) => match (count($type->genericTypes)) {
                    1 => new OptionalOutputMapperCompiler($this->createInner($type->genericTypes[0], $options)),
                    default => throw CannotCreateMapperCompilerException::fromType($type),
                },
                default => match ($type->type->name) {
                    'list' => match (count($type->genericTypes)) {
                        1 => new ListOutputMapperCompiler($this->createInner($type->genericTypes[0], $options)),
                        default => throw CannotCreateMapperCompilerException::fromType($type),
                    },
                    default => $this->createFromGenericType($type, $options),
                },
            };
        }

        if ($type instanceof ArrayTypeNode) {
            return new ArrayOutputMapperCompiler(new PassthroughMapperCompiler(new IdentifierTypeNode('mixed')), $this->createInner($type->type, $options));
        }

        if ($type instanceof ArrayShapeNode) {
            $items = [];

            foreach ($type->items as $item) {
                $key = match (true) {
                    $item->keyName instanceof ConstExprStringNode => $item->keyName->value,
                    $item->keyName instanceof IdentifierTypeNode => $item->keyName->name,
                    default => throw CannotCreateMapperCompilerException::fromType($type),
                };

                $items[] = ['key' => $key, 'mapper' => $this->createInner($item->valueType, $options), 'optional' => $item->optional];
            }

            return new ArrayShapeOutputMapperCompiler($items, $type->sealed);
        }

        if ($type instanceof UnionTypeNode) {
            $isNullable = false;
            $subTypesWithoutNull = [];

            foreach ($type->types as $subType) {
                if ($subType instanceof IdentifierTypeNode && strtolower($subType->name) === 'null') {
                    $isNullable = true;
                } else {
                    $subTypesWithoutNull[] = $subType;
                }
            }

            if ($isNullable && count($subTypesWithoutNull) === 1) {
                return $this->create(new NullableTypeNode($subTypesWithoutNull[0]), $options);
            }
        }

        throw CannotCreateMapperCompilerException::fromType($type);
    }

    /**
     * @param array<string, mixed> $options
     */
    protected function createInner(
        TypeNode $type,
        array $options,
    ): MapperCompiler
    {
        $options[self::DELEGATE_OBJECT_MAPPING] ??= true;
        return $this->create($type, $options);
    }

    /**
     * @param class-string $inputClassName
     * @param array<string, mixed> $options
     */
    protected function createObjectMapperCompiler(
        string $inputClassName,
        array $options,
    ): MapperCompiler
    {
        $classParents = class_parents($inputClassName);
        $classImplements = class_implements($inputClassName);

        if ($classParents === false || $classImplements === false) {
            throw new LogicException("Unable to get class parents or implements for '$inputClassName'.");
        }

        $classLikeNames = [$inputClassName => true, ...$classParents, ...$classImplements];

        foreach ($classLikeNames as $classLikeName => $true) {
            if (isset($this->mapperCompilerFactories[$classLikeName])) {
                $factory = $this->mapperCompilerFactories[$classLikeName];
                return $factory($inputClassName, $options);
            }
        }

        return $this->createObjectMappingByPropertyReading($inputClassName, $options);
    }

    /**
     * @param class-string $inputClassName
     * @param array<string, mixed> $options
     */
    protected function createObjectMappingByPropertyReading(
        string $inputClassName,
        array $options,
    ): MapperCompiler
    {
        $inputType = new IdentifierTypeNode($inputClassName);
        $classReflection = new ReflectionClass($inputClassName);
        $constructor = $classReflection->getConstructor();

        if ($constructor === null) {
            throw CannotCreateMapperCompilerException::fromType($inputType, 'class has no constructor');
        }

        $genericParameters = PhpDocTypeUtils::getGenericTypeDefinition($inputType)->parameters;
        $genericParameterNames = array_column($genericParameters, 'name');
        $options[self::GENERIC_PARAMETERS] = array_fill_keys($genericParameterNames, true);

        $constructorParameterTypes = $this->getConstructorParameterTypes($constructor, $genericParameterNames);

        $propertyMapperCompilers = [];

        foreach ($constructor->getParameters() as $parameter) {
            if (!$parameter->isPromoted()) {
                continue;
            }

            $propertyName = $parameter->getName();
            $type = $constructorParameterTypes[$propertyName];

            $outputKey = $propertyName;

            foreach ($parameter->getAttributes(SourceKey::class) as $attribute) {
                $outputKey = $attribute->newInstance()->key;
            }

            $mapperCompiler = $this->createPropertyMapperCompiler($parameter, $type, $options);

            $propertyMapperCompilers[$propertyName] = [$outputKey, $mapperCompiler];
        }

        return new ObjectOutputMapperCompiler($classReflection->getName(), $propertyMapperCompilers, $genericParameters);
    }

    /**
     * @param list<string> $genericParameterNames
     * @return array<string, TypeNode>
     */
    protected function getConstructorParameterTypes(
        ReflectionMethod $constructor,
        array $genericParameterNames,
    ): array
    {
        $class = $constructor->getDeclaringClass();
        $parameterTypes = [];

        foreach ($constructor->getParameters() as $parameter) {
            $parameterNativeType = $parameter->getType();
            $parameterType = $parameterNativeType !== null ? PhpDocTypeUtils::fromReflectionType($parameterNativeType) : new IdentifierTypeNode('mixed');
            $parameterName = $parameter->getName();
            $parameterTypes[$parameterName] = $parameterType;
        }

        $constructorDocComment = $constructor->getDocComment();

        if ($constructorDocComment !== false) {
            foreach ($this->parsePhpDoc($constructorDocComment)->children as $node) {
                if ($node instanceof PhpDocTagNode && $node->value instanceof ParamTagValueNode) {
                    PhpDocTypeUtils::resolve($node->value->type, $class, $genericParameterNames);
                    $parameterName = substr($node->value->parameterName, 1);
                    $parameterTypes[$parameterName] = $node->value->type;
                }
            }
        }

        foreach ($constructor->getParameters() as $parameter) {
            if (!$parameter->isPromoted()) {
                continue;
            }

            $parameterName = $parameter->getName();
            $propertyDocComment = $class->getProperty($parameterName)->getDocComment();

            if ($propertyDocComment !== false) {
                foreach ($this->parsePhpDoc($propertyDocComment)->children as $node) {
                    if (
                        $node instanceof PhpDocTagNode
                        && $node->value instanceof VarTagValueNode
                        && ($node->value->variableName === '' || substr($node->value->variableName, 1) === $parameterName)
                    ) {
                        PhpDocTypeUtils::resolve($node->value->type, $class, $genericParameterNames);
                        $parameterTypes[$parameterName] = $node->value->type;
                    }
                }
            }
        }

        return $parameterTypes;
    }

    /**
     * @param array<string, mixed> $options
     */
    protected function createPropertyMapperCompiler(
        ReflectionParameter $parameterReflection,
        TypeNode $type,
        array $options,
    ): MapperCompiler
    {
        $mappers = [];

        foreach ($parameterReflection->getAttributes(OutputMapperCompilerProvider::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            $mappers[] = $attribute->newInstance()->getOutputMapperCompiler();
        }

        return match (count($mappers)) {
            0 => $this->createInner($type, $options),
            1 => $mappers[0],
            default => throw CannotCreateMapperCompilerException::withIncompatibleMapperForMethodParameter($mappers[0], $parameterReflection, $type),
        };
    }

    /**
     * @param array<string, mixed> $options
     */
    protected function createFromGenericType(
        GenericTypeNode $type,
        array $options,
    ): MapperCompiler
    {
        if (!class_exists($type->type->name) && !interface_exists($type->type->name)) {
            throw CannotCreateMapperCompilerException::fromType($type, 'there is no class or interface with this name');
        }

        $genericParameters = PhpDocTypeUtils::getGenericTypeDefinition($type->type)->parameters;
        $innerMapperCompilers = [];

        foreach ($type->genericTypes as $index => $genericType) {
            $genericParameter = $genericParameters[$index] ?? throw CannotCreateMapperCompilerException::fromType($type, "generic parameter at index {$index} does not exist");

            if ($genericParameter->bound !== null && !PhpDocTypeUtils::isSubTypeOf($genericType, $genericParameter->bound)) {
                throw CannotCreateMapperCompilerException::fromType($type, "type {$genericType} is not a subtype of {$genericParameter->bound}");
            }

            $innerMapperCompilers[] = $this->createInner($genericType, $options);
        }

        return new DelegateOutputMapperCompiler($type->type->name, $innerMapperCompilers);
    }

    /**
     * @param class-string<BackedEnum> $enumName
     * @param array<string, mixed> $options
     */
    protected function createEnumMapperCompiler(
        string $enumName,
        array $options,
    ): MapperCompiler
    {
        return new EnumOutputMapperCompiler($enumName);
    }

    /**
     * @param class-string $className
     * @param array<string, mixed> $options
     */
    protected function createDateTimeMapperCompiler(
        string $className,
        array $options,
    ): MapperCompiler
    {
        if ($className === DateTimeInterface::class || $className === DateTimeImmutable::class) {
            return new DateTimeImmutableOutputMapperCompiler();
        }

        throw CannotCreateMapperCompilerException::fromType(new IdentifierTypeNode($className));
    }

    protected function parsePhpDoc(string $docComment): PhpDocNode
    {
        $tokens = $this->phpDocLexer->tokenize($docComment);
        return $this->phpDocParser->parse(new TokenIterator($tokens));
    }

}
