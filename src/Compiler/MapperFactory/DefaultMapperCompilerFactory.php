<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\MapperFactory;

use BackedEnum;
use DateTimeImmutable;
use DateTimeInterface;
use LogicException;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprIntegerNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprStringNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ConstTypeNode;
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
use ReflectionEnum;
use ReflectionMethod;
use ReflectionParameter;
use ShipMonk\InputMapper\Compiler\Exception\CannotCreateMapperCompilerException;
use ShipMonk\InputMapper\Compiler\Mapper\Array\ArrayShapeItemMapping;
use ShipMonk\InputMapper\Compiler\Mapper\Array\MapArray;
use ShipMonk\InputMapper\Compiler\Mapper\Array\MapArrayShape;
use ShipMonk\InputMapper\Compiler\Mapper\Array\MapList;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Mixed\MapMixed;
use ShipMonk\InputMapper\Compiler\Mapper\Object\AllowExtraKeys;
use ShipMonk\InputMapper\Compiler\Mapper\Object\DelegateMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Object\Discriminator;
use ShipMonk\InputMapper\Compiler\Mapper\Object\MapDateTimeImmutable;
use ShipMonk\InputMapper\Compiler\Mapper\Object\MapDiscriminatedObject;
use ShipMonk\InputMapper\Compiler\Mapper\Object\MapEnum;
use ShipMonk\InputMapper\Compiler\Mapper\Object\MapObject;
use ShipMonk\InputMapper\Compiler\Mapper\Object\SourceKey;
use ShipMonk\InputMapper\Compiler\Mapper\Optional as OptionalAttribute;
use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapBool;
use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapFloat;
use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapInt;
use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapString;
use ShipMonk\InputMapper\Compiler\Mapper\UndefinedAwareMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Wrapper\ChainMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Wrapper\MapDefaultValue;
use ShipMonk\InputMapper\Compiler\Mapper\Wrapper\MapNullable;
use ShipMonk\InputMapper\Compiler\Mapper\Wrapper\MapOptional;
use ShipMonk\InputMapper\Compiler\Mapper\Wrapper\ValidatedMapperCompiler;
use ShipMonk\InputMapper\Compiler\Type\PhpDocTypeUtils;
use ShipMonk\InputMapper\Compiler\Validator\Array\AssertListLength;
use ShipMonk\InputMapper\Compiler\Validator\Int\AssertIntRange;
use ShipMonk\InputMapper\Compiler\Validator\Int\AssertNegativeInt;
use ShipMonk\InputMapper\Compiler\Validator\Int\AssertNonNegativeInt;
use ShipMonk\InputMapper\Compiler\Validator\Int\AssertNonPositiveInt;
use ShipMonk\InputMapper\Compiler\Validator\Int\AssertPositiveInt;
use ShipMonk\InputMapper\Compiler\Validator\ValidatorCompiler;
use ShipMonk\InputMapper\Runtime\Optional;
use function array_column;
use function array_fill_keys;
use function class_exists;
use function class_implements;
use function class_parents;
use function count;
use function interface_exists;
use function is_array;
use function strcasecmp;
use function strtolower;
use function substr;

class DefaultMapperCompilerFactory implements MapperCompilerFactory
{

    final public const DELEGATE_OBJECT_MAPPING = 'delegateObjectMapping';
    final public const GENERIC_PARAMETERS = 'genericParameters';
    final public const DEFAULT_VALUE = 'defaultValue';

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
     * @template T of object
     * @param class-string<T> $className
     * @param callable(class-string<T>, array<string, mixed>): MapperCompiler $factory
     */
    public function setMapperCompilerFactory(string $className, callable $factory): void
    {
        $this->mapperCompilerFactories[$className] = $factory; // @phpstan-ignore-line
    }

    /**
     * @param array<string, mixed> $options
     */
    public function create(TypeNode $type, array $options = []): MapperCompiler
    {
        if ($type instanceof IdentifierTypeNode) {
            if (!PhpDocTypeUtils::isKeyword($type)) {
                if (isset($options[self::DELEGATE_OBJECT_MAPPING]) && $options[self::DELEGATE_OBJECT_MAPPING] === true) {
                    if (!class_exists($type->name) && !interface_exists($type->name)) {
                        if (!isset($options[self::GENERIC_PARAMETERS]) || !is_array($options[self::GENERIC_PARAMETERS]) || !isset($options[self::GENERIC_PARAMETERS][$type->name])) {
                            throw CannotCreateMapperCompilerException::fromType($type, 'there is no class, interface or enum with this name');
                        }
                    }

                    return new DelegateMapperCompiler($type->name);
                }

                if (!class_exists($type->name) && !interface_exists($type->name)) {
                    throw CannotCreateMapperCompilerException::fromType($type, 'there is no class, interface or enum with this name');
                }

                return $this->createObjectMapperCompiler($type->name, $options);
            }

            return match (strtolower($type->name)) {
                'array' => new MapArray(new MapMixed(), new MapMixed()),
                'bool' => new MapBool(),
                'float' => new MapFloat(),
                'int' => new MapInt(),
                'mixed' => new MapMixed(),
                'string' => new MapString(),

                default => match ($type->name) {
                    'list' => new MapList(new MapMixed()),
                    'non-empty-list' => new ValidatedMapperCompiler(new MapList(new MapMixed()), [new AssertListLength(min: 1)]),
                    'negative-int' => new ValidatedMapperCompiler($this->createInner(new IdentifierTypeNode('int'), $options), [new AssertNegativeInt()]),
                    'non-negative-int' => new ValidatedMapperCompiler($this->createInner(new IdentifierTypeNode('int'), $options), [new AssertNonNegativeInt()]),
                    'non-positive-int' => new ValidatedMapperCompiler($this->createInner(new IdentifierTypeNode('int'), $options), [new AssertNonPositiveInt()]),
                    'positive-int' => new ValidatedMapperCompiler($this->createInner(new IdentifierTypeNode('int'), $options), [new AssertPositiveInt()]),
                    default => throw CannotCreateMapperCompilerException::fromType($type),
                },
            };
        }

        if ($type instanceof NullableTypeNode) {
            return new MapNullable($this->createInner($type->type, $options));
        }

        if ($type instanceof GenericTypeNode) {
            return match (strtolower($type->type->name)) {
                'array' => match (count($type->genericTypes)) {
                    1 => new MapArray(new MapMixed(), $this->createInner($type->genericTypes[0], $options)),
                    2 => new MapArray($this->createInner($type->genericTypes[0], $options), $this->createInner($type->genericTypes[1], $options)),
                    default => throw CannotCreateMapperCompilerException::fromType($type),
                },
                'int' => match (count($type->genericTypes)) {
                    2 => new ValidatedMapperCompiler($this->createInner(new IdentifierTypeNode('int'), $options), [
                        new AssertIntRange(
                            gte: $this->resolveIntegerBoundary($type, $type->genericTypes[0], 'min'),
                            lte: $this->resolveIntegerBoundary($type, $type->genericTypes[1], 'max'),
                        ),
                    ]),
                    default => throw CannotCreateMapperCompilerException::fromType($type),
                },
                default => match ($type->type->name) {
                    'list' => match (count($type->genericTypes)) {
                        1 => new MapList($this->createInner($type->genericTypes[0], $options)),
                        default => throw CannotCreateMapperCompilerException::fromType($type),
                    },
                    'non-empty-list' => match (count($type->genericTypes)) {
                        1 => new ValidatedMapperCompiler(new MapList($this->createInner($type->genericTypes[0], $options)), [new AssertListLength(min: 1)]),
                        default => throw CannotCreateMapperCompilerException::fromType($type),
                    },
                    Optional::class => match (count($type->genericTypes)) {
                        1 => new MapOptional($this->createInner($type->genericTypes[0], $options)),
                        default => throw CannotCreateMapperCompilerException::fromType($type),
                    },
                    default => $this->createFromGenericType($type, $options),
                },
            };
        }

        if ($type instanceof ArrayTypeNode) {
            return new MapArray(new MapMixed(), $this->createInner($type->type, $options));
        }

        if ($type instanceof ArrayShapeNode) {
            $items = [];

            foreach ($type->items as $item) {
                $key = match (true) {
                    $item->keyName instanceof ConstExprStringNode => $item->keyName->value,
                    $item->keyName instanceof IdentifierTypeNode => $item->keyName->name,
                    default => throw CannotCreateMapperCompilerException::fromType($type),
                };

                $items[] = new ArrayShapeItemMapping($key, $this->createInner($item->valueType, $options), $item->optional);
            }

            return new MapArrayShape($items, $type->sealed);
        }

        if ($type instanceof UnionTypeNode) {
            $isNullable = false;
            $subTypesWithoutNull = [];

            foreach ($type->types as $subType) {
                if ($subType instanceof IdentifierTypeNode && strcasecmp($subType->name, 'null') === 0) {
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
     * @param  array<string, mixed> $options
     */
    protected function createInner(TypeNode $type, array $options): MapperCompiler
    {
        $options[self::DELEGATE_OBJECT_MAPPING] ??= true;
        return $this->create($type, $options);
    }

    /**
     * @param  array<string, mixed> $options
     */
    protected function createFromGenericType(GenericTypeNode $type, array $options): MapperCompiler
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

        return new DelegateMapperCompiler($type->type->name, $innerMapperCompilers);
    }

    /**
     * @param  class-string         $inputClassName
     * @param  array<string, mixed> $options
     */
    protected function createObjectMapperCompiler(string $inputClassName, array $options): MapperCompiler
    {
        $classParents = class_parents($inputClassName);
        $classImplements = class_implements($inputClassName);

        if ($classParents === false || $classImplements === false) {
            throw new LogicException("Unable to get class parents or implements for '$inputClassName'.");
        }

        $classLikeNames = [$inputClassName => true, ...$classParents, ...$classImplements];

        foreach ($classLikeNames as $classLikeName => $_) {
            if (isset($this->mapperCompilerFactories[$classLikeName])) {
                $factory = $this->mapperCompilerFactories[$classLikeName];
                return $factory($inputClassName, $options);
            }
        }

        $classReflection = new ReflectionClass($inputClassName);

        foreach ($classReflection->getAttributes(Discriminator::class) as $discriminatorAttribute) {
            return $this->createDiscriminatorObjectMapping($inputClassName, $discriminatorAttribute->newInstance(), $options);
        }

        return $this->createObjectMappingByConstructorInvocation($inputClassName, $options);
    }

    /**
     * @param  array<string, mixed> $options
     */
    protected function createObjectMappingByConstructorInvocation(
        string $inputClassName,
        array $options,
    ): MapperCompiler
    {
        $classReflection = new ReflectionClass($inputClassName);
        $inputType = new IdentifierTypeNode($inputClassName);
        $constructor = $classReflection->getConstructor();

        if ($constructor === null) {
            throw CannotCreateMapperCompilerException::fromType($inputType, 'class has no constructor');
        }

        if (!$constructor->isPublic()) {
            throw CannotCreateMapperCompilerException::fromType($inputType, 'class has a non-public constructor');
        }

        $genericParameters = PhpDocTypeUtils::getGenericTypeDefinition($inputType)->parameters;
        $genericParameterNames = array_column($genericParameters, 'name');
        $options[self::GENERIC_PARAMETERS] = array_fill_keys($genericParameterNames, true);

        $constructorParameterMapperCompilers = [];
        $constructorParameterTypes = $this->getConstructorParameterTypes($constructor, $genericParameterNames);

        foreach ($constructor->getParameters() as $parameter) {
            $name = $parameter->getName();
            $type = $constructorParameterTypes[$name];

            foreach ($parameter->getAttributes(SourceKey::class) as $attribute) {
                $name = $attribute->newInstance()->key;
            }

            $constructorParameterMapperCompilers[$name] = $this->createParameterMapperCompiler($parameter, $type, $options);
        }

        $allowExtraKeys = count($classReflection->getAttributes(AllowExtraKeys::class)) > 0;
        return new MapObject($classReflection->getName(), $constructorParameterMapperCompilers, $allowExtraKeys, $genericParameters);
    }

    /**
     * @param class-string $inputClassName
     * @param array<string, mixed> $options
     */
    public function createDiscriminatorObjectMapping(
        string $inputClassName,
        Discriminator $discriminatorAttribute,
        array $options,
    ): MapperCompiler
    {
        $objectMappers = [];

        foreach ($discriminatorAttribute->mapping as $key => $mappingClass) {
            $objectMappers[$key] = $this->createObjectMapperCompiler($mappingClass, $options);
        }

        return new MapDiscriminatedObject(
            $inputClassName,
            new MapString(),
            $discriminatorAttribute->key,
            $objectMappers,
        );
    }

    /**
     * @param list<string> $genericParameterNames
     * @return array<string, TypeNode>
     */
    protected function getConstructorParameterTypes(ReflectionMethod $constructor, array $genericParameterNames): array
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
    protected function createParameterMapperCompiler(
        ReflectionParameter $parameterReflection,
        TypeNode $type,
        array $options,
    ): MapperCompiler
    {
        $mappers = [];
        $validators = [];

        foreach ($parameterReflection->getAttributes(MapperCompiler::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            $mappers[] = $attribute->newInstance();
        }

        foreach ($parameterReflection->getAttributes(ValidatorCompiler::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            $validators[] = $attribute->newInstance();
        }

        $mapper = match (count($mappers)) {
            0 => $this->createInner($type, $options),
            1 => $mappers[0],
            default => new ChainMapperCompiler($mappers),
        };

        foreach ($parameterReflection->getAttributes(OptionalAttribute::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            $mapper = new MapDefaultValue($mapper, $attribute->newInstance()->default);
        }

        if (!PhpDocTypeUtils::isSubTypeOf($mapper->getOutputType(), $type)) {
            throw CannotCreateMapperCompilerException::withIncompatibleMapperForMethodParameter($mapper, $parameterReflection, $type);
        }

        if ($mapper instanceof UndefinedAwareMapperCompiler) {
            if (!PhpDocTypeUtils::isSubTypeOf($mapper->getDefaultValueType(), $type)) {
                throw CannotCreateMapperCompilerException::withIncompatibleDefaultValueParameter($mapper, $parameterReflection, $type);
            }
        }

        foreach ($validators as $validator) {
            $mapper = $this->addValidator($mapper, $validator);
        }

        return $mapper;
    }

    protected function addValidator(
        MapperCompiler $mapperCompiler,
        ValidatorCompiler $validatorCompiler
    ): MapperCompiler
    {
        $validatorInputType = $validatorCompiler->getInputType();
        $mapperOutputType = $mapperCompiler->getOutputType();

        if (PhpDocTypeUtils::isSubTypeOf($mapperOutputType, $validatorInputType)) {
            return new ValidatedMapperCompiler($mapperCompiler, [$validatorCompiler]);
        }

        if ($mapperCompiler instanceof MapDefaultValue) {
            return new MapDefaultValue($this->addValidator($mapperCompiler->mapperCompiler, $validatorCompiler), $mapperCompiler->defaultValue);
        }

        if ($mapperCompiler instanceof MapNullable) {
            return new MapNullable($this->addValidator($mapperCompiler->innerMapperCompiler, $validatorCompiler));
        }

        if ($mapperCompiler instanceof MapOptional) {
            return new MapOptional($this->addValidator($mapperCompiler->mapperCompiler, $validatorCompiler));
        }

        throw CannotCreateMapperCompilerException::withIncompatibleValidator($validatorCompiler, $mapperCompiler);
    }

    /**
     * @param class-string<BackedEnum> $enumName
     * @param array<string, mixed> $options
     */
    protected function createEnumMapperCompiler(string $enumName, array $options): MapperCompiler
    {
        $enumReflection = new ReflectionEnum($enumName);
        $backingReflectionType = $enumReflection->getBackingType() ?? throw new LogicException("Enum {$enumName} has no backing type");
        $backingType = PhpDocTypeUtils::fromReflectionType($backingReflectionType);
        $backingTypeMapperCompiler = $this->createInner($backingType, $options);

        return new MapEnum($enumName, $backingTypeMapperCompiler);
    }

    /**
     * @param class-string $className
     * @param array<string, mixed> $options
     */
    protected function createDateTimeMapperCompiler(string $className, array $options): MapperCompiler
    {
        if ($className === DateTimeInterface::class || $className === DateTimeImmutable::class) {
            return new MapDateTimeImmutable();
        }

        throw CannotCreateMapperCompilerException::fromType(new IdentifierTypeNode($className));
    }

    protected function resolveIntegerBoundary(TypeNode $type, TypeNode $boundaryType, string $extremeName): ?int
    {
        if ($boundaryType instanceof ConstTypeNode && $boundaryType->constExpr instanceof ConstExprIntegerNode) {
            return (int) $boundaryType->constExpr->value;
        }

        if ($boundaryType instanceof IdentifierTypeNode && $boundaryType->name === $extremeName) {
            return null;
        }

        throw CannotCreateMapperCompilerException::fromType($type, "integer boundary {$boundaryType} is not supported");
    }

    protected function parsePhpDoc(string $docComment): PhpDocNode
    {
        $tokens = $this->phpDocLexer->tokenize($docComment);
        return $this->phpDocParser->parse(new TokenIterator($tokens));
    }

}
