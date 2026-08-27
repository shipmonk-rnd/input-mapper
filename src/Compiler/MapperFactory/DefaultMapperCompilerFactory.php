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
use ReflectionEnum;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;
use ShipMonk\InputMapper\Compiler\Attribute\AllowExtraKeys;
use ShipMonk\InputMapper\Compiler\Attribute\ArrayShapeItemMapping;
use ShipMonk\InputMapper\Compiler\Attribute\Discriminator;
use ShipMonk\InputMapper\Compiler\Attribute\MapArray;
use ShipMonk\InputMapper\Compiler\Attribute\MapArrayShape;
use ShipMonk\InputMapper\Compiler\Attribute\MapBool;
use ShipMonk\InputMapper\Compiler\Attribute\MapChain;
use ShipMonk\InputMapper\Compiler\Attribute\MapDateTimeImmutable;
use ShipMonk\InputMapper\Compiler\Attribute\MapDefaultValue;
use ShipMonk\InputMapper\Compiler\Attribute\MapDelegate;
use ShipMonk\InputMapper\Compiler\Attribute\MapDiscriminatedObject;
use ShipMonk\InputMapper\Compiler\Attribute\MapEnum;
use ShipMonk\InputMapper\Compiler\Attribute\MapFloat;
use ShipMonk\InputMapper\Compiler\Attribute\MapInt;
use ShipMonk\InputMapper\Compiler\Attribute\MapList;
use ShipMonk\InputMapper\Compiler\Attribute\MapMixed;
use ShipMonk\InputMapper\Compiler\Attribute\MapNullable;
use ShipMonk\InputMapper\Compiler\Attribute\MapObject;
use ShipMonk\InputMapper\Compiler\Attribute\MapOptional;
use ShipMonk\InputMapper\Compiler\Attribute\MapString;
use ShipMonk\InputMapper\Compiler\Attribute\MapValidated;
use ShipMonk\InputMapper\Compiler\Attribute\Optional as OptionalAttribute;
use ShipMonk\InputMapper\Compiler\Attribute\SourceKey;
use ShipMonk\InputMapper\Compiler\Exception\CannotCreateMapperCompilerException;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompilerProvider;
use ShipMonk\InputMapper\Compiler\Mapper\OutputMapperCompilerProvider;
use ShipMonk\InputMapper\Compiler\Mapper\UndefinedAwareMapperCompiler;
use ShipMonk\InputMapper\Compiler\PropertyNameTransformer\PropertyNameTransformer;
use ShipMonk\InputMapper\Compiler\Type\PhpDocTypeUtils;
use ShipMonk\InputMapper\Compiler\Validator\Array\AssertListLength;
use ShipMonk\InputMapper\Compiler\Validator\Int\AssertIntRange;
use ShipMonk\InputMapper\Compiler\Validator\Int\AssertNegativeInt;
use ShipMonk\InputMapper\Compiler\Validator\Int\AssertNonNegativeInt;
use ShipMonk\InputMapper\Compiler\Validator\Int\AssertNonPositiveInt;
use ShipMonk\InputMapper\Compiler\Validator\Int\AssertPositiveInt;
use ShipMonk\InputMapper\Compiler\Validator\String\AssertStringNonEmpty;
use ShipMonk\InputMapper\Compiler\Validator\ValidatorCompiler;
use ShipMonk\InputMapper\Runtime\Optional;
use function array_column;
use function array_fill_keys;
use function array_map;
use function class_exists;
use function class_implements;
use function class_parents;
use function count;
use function interface_exists;
use function is_array;
use function strtolower;
use function substr;

class DefaultMapperCompilerFactory implements MapperCompilerFactory
{

    final public const DELEGATE_OBJECT_MAPPING = 'delegateObjectMapping';
    final public const GENERIC_PARAMETERS = 'genericParameters';

    /**
     * @param array<class-string, callable(class-string, array<string, mixed>): MapperCompilerProvider> $mapperCompilerFactories
     */
    public function __construct(
        protected readonly Lexer $phpDocLexer,
        protected readonly PhpDocParser $phpDocParser,
        protected array $mapperCompilerFactories = [],
        protected readonly ?PropertyNameTransformer $propertyNameTransformer = null,
    )
    {
        $this->setMapperCompilerFactory(BackedEnum::class, $this->createEnumMapperCompilerProvider(...));
        $this->setMapperCompilerFactory(DateTimeInterface::class, $this->createDateTimeMapperCompilerProvider(...));
    }

    /**
     * @param class-string<T> $className
     * @param callable(class-string<T>, array<string, mixed>): MapperCompilerProvider $factory
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
    ): MapperCompilerProvider
    {
        if ($type instanceof IdentifierTypeNode) {
            if (!PhpDocTypeUtils::isKeyword($type)) {
                if (isset($options[self::DELEGATE_OBJECT_MAPPING]) && $options[self::DELEGATE_OBJECT_MAPPING] === true) {
                    if (!class_exists($type->name) && !interface_exists($type->name)) {
                        if (!isset($options[self::GENERIC_PARAMETERS]) || !is_array($options[self::GENERIC_PARAMETERS]) || !isset($options[self::GENERIC_PARAMETERS][$type->name])) {
                            throw CannotCreateMapperCompilerException::fromType($type, 'there is no class, interface or enum with this name');
                        }
                    }

                    return new MapDelegate($type->name);
                }

                if (!class_exists($type->name) && !interface_exists($type->name)) {
                    throw CannotCreateMapperCompilerException::fromType($type, 'there is no class, interface or enum with this name');
                }

                return $this->createObjectMapperCompilerProvider($type->name, $options);
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
                    'non-empty-list' => new MapValidated(new MapList(new MapMixed()), [new AssertListLength(min: 1)]),
                    'non-empty-string' => new MapValidated($this->createInner(new IdentifierTypeNode('string'), $options), [new AssertStringNonEmpty()]),
                    'negative-int' => new MapValidated($this->createInner(new IdentifierTypeNode('int'), $options), [new AssertNegativeInt()]),
                    'non-negative-int' => new MapValidated($this->createInner(new IdentifierTypeNode('int'), $options), [new AssertNonNegativeInt()]),
                    'non-positive-int' => new MapValidated($this->createInner(new IdentifierTypeNode('int'), $options), [new AssertNonPositiveInt()]),
                    'positive-int' => new MapValidated($this->createInner(new IdentifierTypeNode('int'), $options), [new AssertPositiveInt()]),
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
                    2 => new MapValidated($this->createInner(new IdentifierTypeNode('int'), $options), [
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
                        1 => new MapValidated(new MapList($this->createInner($type->genericTypes[0], $options)), [new AssertListLength(min: 1)]),
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
    ): MapperCompilerProvider
    {
        $options[self::DELEGATE_OBJECT_MAPPING] ??= true;
        return $this->create($type, $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    protected function createFromGenericType(
        GenericTypeNode $type,
        array $options,
    ): MapperCompilerProvider
    {
        if (!class_exists($type->type->name) && !interface_exists($type->type->name)) {
            throw CannotCreateMapperCompilerException::fromType($type, 'there is no class or interface with this name');
        }

        $genericParameters = PhpDocTypeUtils::getGenericTypeDefinition($type->type)->parameters;
        $innerMapperCompilerProviders = [];

        foreach ($type->genericTypes as $index => $genericType) {
            $genericParameter = $genericParameters[$index] ?? throw CannotCreateMapperCompilerException::fromType($type, "generic parameter at index {$index} does not exist");

            if ($genericParameter->bound !== null && !PhpDocTypeUtils::isSubTypeOf($genericType, $genericParameter->bound)) {
                throw CannotCreateMapperCompilerException::fromType($type, "type {$genericType} is not a subtype of {$genericParameter->bound}");
            }

            $innerMapperCompilerProviders[] = $this->createInner($genericType, $options);
        }

        return new MapDelegate($type->type->name, $innerMapperCompilerProviders);
    }

    /**
     * @param class-string $className
     * @param array<string, mixed> $options
     */
    protected function createObjectMapperCompilerProvider(
        string $className,
        array $options,
    ): MapperCompilerProvider
    {
        $factory = $this->findMapperCompilerFactory($className);

        if ($factory !== null) {
            return $factory($className, $options);
        }

        $classReflection = new ReflectionClass($className);

        foreach ($classReflection->getAttributes(Discriminator::class) as $discriminatorAttribute) {
            return $this->createDiscriminatorObjectMapping($className, $discriminatorAttribute->newInstance());
        }

        $inputType = new IdentifierTypeNode($className);
        $genericParameters = PhpDocTypeUtils::getGenericTypeDefinition($inputType)->parameters;
        $genericParameterNames = array_column($genericParameters, 'name');
        $options[self::GENERIC_PARAMETERS] = array_fill_keys($genericParameterNames, true);

        [$constructorArgsProviders, $allowExtraKeys] = $this->createConstructorArgsProviders($classReflection, $genericParameterNames, $options);
        $propertyProviders = $this->createPropertyProviders($classReflection, $genericParameterNames, $options);

        return new MapObject($className, $constructorArgsProviders, $allowExtraKeys, $propertyProviders, $genericParameters);
    }

    /**
     * @param ReflectionClass<object> $classReflection
     * @param list<string> $genericParameterNames
     * @param array<string, mixed> $options
     * @return array{array<string, MapperCompilerProvider>, bool} [constructorArgsProviders, allowExtraKeys]
     */
    protected function createConstructorArgsProviders(
        ReflectionClass $classReflection,
        array $genericParameterNames,
        array $options,
    ): array
    {
        $inputType = new IdentifierTypeNode($classReflection->getName());
        $constructor = $classReflection->getConstructor();

        if ($constructor === null) {
            throw CannotCreateMapperCompilerException::fromType($inputType, 'class has no constructor');
        }

        if (!$constructor->isPublic()) {
            throw CannotCreateMapperCompilerException::fromType($inputType, 'class has a non-public constructor');
        }

        $constructorArgsProviders = [];
        $constructorParameterTypes = $this->getConstructorParameterTypes($constructor, $genericParameterNames);

        foreach ($constructor->getParameters() as $parameter) {
            $parameterName = $parameter->getName();
            $type = $constructorParameterTypes[$parameterName];
            $name = $this->resolveSourceKey($parameter, $classReflection);

            if (isset($constructorArgsProviders[$name])) {
                throw CannotCreateMapperCompilerException::fromType($inputType, "multiple constructor parameters map to source key '{$name}'");
            }

            $constructorArgsProviders[$name] = $this->createParameterMapperCompilerProvider($parameter, $type, $options);
        }

        $allowExtraKeys = count($classReflection->getAttributes(AllowExtraKeys::class)) > 0;
        return [$constructorArgsProviders, $allowExtraKeys];
    }

    /**
     * @param ReflectionClass<object> $classReflection
     * @param list<string> $genericParameterNames
     * @param array<string, mixed> $options
     * @return array<string, array{string, OutputMapperCompilerProvider}> propertyName => [outputKey, OutputMapperCompilerProvider]
     */
    protected function createPropertyProviders(
        ReflectionClass $classReflection,
        array $genericParameterNames,
        array $options,
    ): array
    {
        $inputType = new IdentifierTypeNode($classReflection->getName());

        /** @var array<string, array<string, TypeNode>> $constructorTypesByClass */
        $constructorTypesByClass = [];

        $propertyProviders = [];
        $outputKeys = [];

        foreach ($classReflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if (!$property->isReadOnly()) {
                continue;
            }

            $propertyName = $property->getName();
            $declaringClass = $property->getDeclaringClass();
            $declaringClassName = $declaringClass->getName();

            if (!isset($constructorTypesByClass[$declaringClassName])) {
                $declaringConstructor = $declaringClass->getConstructor();

                if ($declaringConstructor === null) {
                    throw CannotCreateMapperCompilerException::fromType($inputType, "class {$declaringClassName} has no constructor");
                }

                $constructorTypesByClass[$declaringClassName] = $this->getConstructorParameterTypes($declaringConstructor, $genericParameterNames);
            }

            $type = $constructorTypesByClass[$declaringClassName][$propertyName]
                ?? throw CannotCreateMapperCompilerException::fromType($inputType, "cannot determine type for property {$propertyName}");

            $outputKey = $this->resolveSourceKey($property, $classReflection);

            if (isset($outputKeys[$outputKey])) {
                throw CannotCreateMapperCompilerException::fromType($inputType, "multiple properties map to source key '{$outputKey}'");
            }

            $outputKeys[$outputKey] = true;

            $provider = $this->createPropertyMapperCompilerProvider($property, $type, $options);

            $propertyProviders[$propertyName] = [$outputKey, $provider];
        }

        return $propertyProviders;
    }

    /**
     * @param class-string $className
     */
    public function createDiscriminatorObjectMapping(
        string $className,
        Discriminator $discriminatorAttribute,
    ): MapperCompilerProvider
    {
        $inputType = new IdentifierTypeNode($className);
        $genericParameters = PhpDocTypeUtils::getGenericTypeDefinition($inputType)->parameters;

        $this->checkDiscriminatedClass($inputType, $className, $discriminatorAttribute);

        $subtypeProviders = array_map(
            static fn (string $subtypeClassName): MapperCompilerProvider => new MapDelegate($subtypeClassName),
            $discriminatorAttribute->mapping,
        );

        return new MapDiscriminatedObject(
            $className,
            $discriminatorAttribute->key,
            $subtypeProviders,
            $genericParameters,
        );
    }

    /**
     * The key that the mapper reads for a constructor parameter, or writes for a property.
     *
     * @param ReflectionClass<object> $classReflection
     */
    protected function resolveSourceKey(
        ReflectionParameter|ReflectionProperty $reflection,
        ReflectionClass $classReflection,
    ): string
    {
        $sourceKeyAttributes = $reflection->getAttributes(SourceKey::class);

        if (count($sourceKeyAttributes) > 0) {
            return $sourceKeyAttributes[0]->newInstance()->key;
        }

        if ($this->propertyNameTransformer !== null) {
            return $this->propertyNameTransformer->transform($reflection->getName(), $classReflection->getName());
        }

        return $reflection->getName();
    }

    /**
     * @param class-string $className
     * @return (callable(class-string, array<string, mixed>): MapperCompilerProvider)|null
     */
    protected function findMapperCompilerFactory(string $className): ?callable
    {
        $classParents = class_parents($className);
        $classImplements = class_implements($className);

        if ($classParents === false || $classImplements === false) {
            throw new LogicException("Unable to get class parents or implements for '$className'.");
        }

        foreach ([$className => true, ...$classParents, ...$classImplements] as $classLikeName => $true) {
            if (isset($this->mapperCompilerFactories[$classLikeName])) {
                return $this->mapperCompilerFactories[$classLikeName];
            }
        }

        return null;
    }

    /**
     * The discriminated mapper delegates the whole mapping to the subtypes, so it never reads #[AllowExtraKeys] of the
     * annotated class, and every input reaches a subtype mapper together with the discriminator key.
     *
     * @param class-string $className
     */
    protected function checkDiscriminatedClass(
        IdentifierTypeNode $inputType,
        string $className,
        Discriminator $discriminatorAttribute,
    ): void
    {
        if (count($discriminatorAttribute->mapping) === 0) {
            throw CannotCreateMapperCompilerException::fromType($inputType, 'discriminator mapping is empty, so no input can be mapped');
        }

        $classReflection = new ReflectionClass($className);

        if (count($classReflection->getAttributes(AllowExtraKeys::class)) > 0) {
            throw CannotCreateMapperCompilerException::fromType(
                $inputType,
                'the discriminated mapping delegates to subtypes and never reads #[AllowExtraKeys] here, put it on every subtype instead',
            );
        }

        foreach ($discriminatorAttribute->mapping as $key => $subtypeClassName) {
            if (!$this->subtypeAcceptsDiscriminatorKey($subtypeClassName, $discriminatorAttribute->key)) {
                $discriminatorKey = $discriminatorAttribute->key;
                throw CannotCreateMapperCompilerException::fromType(
                    $inputType,
                    "subtype {$subtypeClassName} mapped to \"{$key}\" accepts no \"{$discriminatorKey}\" key, so every input would fail; "
                        . 'add a constructor parameter for that key or #[AllowExtraKeys] to the subtype',
                );
            }
        }
    }

    /**
     * Answers only for subtypes that the standard object mapping covers. A subtype with its own discriminator, or one
     * that a registered factory maps, has a shape this method cannot know, and its own mapping decides.
     *
     * @param class-string $subtypeClassName
     */
    protected function subtypeAcceptsDiscriminatorKey(
        string $subtypeClassName,
        string $discriminatorKey,
    ): bool
    {
        if (!class_exists($subtypeClassName)) {
            return true;
        }

        $subtypeReflection = new ReflectionClass($subtypeClassName);

        if (count($subtypeReflection->getAttributes(Discriminator::class)) > 0) {
            return true;
        }

        if (count($subtypeReflection->getAttributes(AllowExtraKeys::class)) > 0) {
            return true;
        }

        if ($this->findMapperCompilerFactory($subtypeClassName) !== null) {
            return true;
        }

        $constructor = $subtypeReflection->getConstructor();

        if ($constructor === null || !$constructor->isPublic()) {
            return true;
        }

        foreach ($constructor->getParameters() as $parameter) {
            if ($this->resolveSourceKey($parameter, $subtypeReflection) === $discriminatorKey) {
                return true;
            }
        }

        return false;
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
    protected function createParameterMapperCompilerProvider(
        ReflectionParameter $parameterReflection,
        TypeNode $type,
        array $options,
    ): MapperCompilerProvider
    {
        /** @var list<MapperCompilerProvider> $providers */
        $providers = [];
        $validators = [];

        foreach ($parameterReflection->getAttributes(MapperCompilerProvider::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            $providers[] = $attribute->newInstance();
        }

        foreach ($parameterReflection->getAttributes(ValidatorCompiler::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            $validators[] = $attribute->newInstance();
        }

        $provider = match (count($providers)) {
            0 => $this->createInner($type, $options),
            1 => $providers[0],
            default => new MapChain($providers),
        };

        foreach ($validators as $validator) {
            $provider = $this->addValidatorProvider($provider, $validator);
        }

        foreach ($parameterReflection->getAttributes(OptionalAttribute::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            $provider = new MapDefaultValue($provider, $attribute->newInstance()->default);
        }

        $mapper = $provider->getInputMapperCompiler();

        if (!PhpDocTypeUtils::isSubTypeOf($mapper->getOutputType(), $type)) {
            throw CannotCreateMapperCompilerException::withIncompatibleMapperForMethodParameter($mapper, $parameterReflection, $type);
        }

        if ($mapper instanceof UndefinedAwareMapperCompiler) {
            if (!PhpDocTypeUtils::isSubTypeOf($mapper->getDefaultValueType(), $type)) {
                throw CannotCreateMapperCompilerException::withIncompatibleDefaultValueParameter($mapper, $parameterReflection, $type);
            }
        }

        return $provider;
    }

    /**
     * @param array<string, mixed> $options
     */
    protected function createPropertyMapperCompilerProvider(
        ReflectionProperty $propertyReflection,
        TypeNode $type,
        array $options,
    ): OutputMapperCompilerProvider
    {
        /** @var list<OutputMapperCompilerProvider> $outputProviders */
        $outputProviders = [];

        foreach ($propertyReflection->getAttributes(OutputMapperCompilerProvider::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            $outputProviders[] = $attribute->newInstance();
        }

        return match (count($outputProviders)) {
            0 => $this->createInner($type, $options),
            1 => $outputProviders[0],
            default => throw CannotCreateMapperCompilerException::fromType($type, 'multiple OutputMapperCompilerProvider attributes found on property $' . $propertyReflection->getName()),
        };
    }

    protected function addValidatorProvider(
        MapperCompilerProvider $provider,
        ValidatorCompiler $validatorCompiler,
    ): MapperCompilerProvider
    {
        if ($provider instanceof MapDefaultValue) {
            return new MapDefaultValue(
                $this->addValidatorProvider($provider->mapperCompilerProvider, $validatorCompiler),
                $provider->defaultValue,
            );
        }

        if ($provider instanceof MapOptional) {
            return new MapOptional(
                $this->addValidatorProvider($provider->mapperCompilerProvider, $validatorCompiler),
            );
        }

        if ($provider instanceof MapNullable) {
            return new MapNullable(
                $this->addValidatorProvider($provider->innerMapperCompilerProvider, $validatorCompiler),
            );
        }

        $mapperOutputType = $provider->getInputMapperCompiler()->getOutputType();
        $validatorInputType = $validatorCompiler->getInputType();

        if (PhpDocTypeUtils::isSubTypeOf($mapperOutputType, $validatorInputType)) {
            return new MapValidated($provider, [$validatorCompiler]);
        }

        throw CannotCreateMapperCompilerException::withIncompatibleValidator($validatorCompiler, $provider->getInputMapperCompiler());
    }

    /**
     * @param class-string<BackedEnum> $enumName
     * @param array<string, mixed> $options
     */
    protected function createEnumMapperCompilerProvider(
        string $enumName,
        array $options,
    ): MapperCompilerProvider
    {
        $enumReflection = new ReflectionEnum($enumName);
        $backingReflectionType = $enumReflection->getBackingType() ?? throw new LogicException("Enum {$enumName} has no backing type");
        $backingType = PhpDocTypeUtils::fromReflectionType($backingReflectionType);
        $backingTypeMapperCompilerProvider = $this->createInner($backingType, $options);

        return new MapEnum($enumName, $backingTypeMapperCompilerProvider);
    }

    /**
     * @param class-string $className
     * @param array<string, mixed> $options
     */
    protected function createDateTimeMapperCompilerProvider(
        string $className,
        array $options,
    ): MapperCompilerProvider
    {
        if ($className === DateTimeInterface::class || $className === DateTimeImmutable::class) {
            return new MapDateTimeImmutable();
        }

        throw CannotCreateMapperCompilerException::fromType(new IdentifierTypeNode($className));
    }

    protected function resolveIntegerBoundary(
        TypeNode $type,
        TypeNode $boundaryType,
        string $extremeName,
    ): ?int
    {
        $boundaryValue = PhpDocTypeUtils::tryResolveIntegerBoundary($boundaryType);

        if ($boundaryValue !== null) {
            return $boundaryValue;
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
