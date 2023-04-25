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
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ConstTypeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\NullableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionEnum;
use ReflectionMethod;
use ReflectionParameter;
use ShipMonk\InputMapper\Compiler\Exception\CannotInferMapperException;
use ShipMonk\InputMapper\Compiler\Mapper\Array\ArrayShapeItemMapping;
use ShipMonk\InputMapper\Compiler\Mapper\Array\MapArray;
use ShipMonk\InputMapper\Compiler\Mapper\Array\MapArrayShape;
use ShipMonk\InputMapper\Compiler\Mapper\Array\MapList;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Mixed\MapMixed;
use ShipMonk\InputMapper\Compiler\Mapper\Object\DelegateMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Object\MapDateTimeImmutable;
use ShipMonk\InputMapper\Compiler\Mapper\Object\MapEnum;
use ShipMonk\InputMapper\Compiler\Mapper\Object\MapObject;
use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapBool;
use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapFloat;
use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapInt;
use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapString;
use ShipMonk\InputMapper\Compiler\Mapper\Wrapper\ChainMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Wrapper\MapNullable;
use ShipMonk\InputMapper\Compiler\Mapper\Wrapper\MapOptional;
use ShipMonk\InputMapper\Compiler\Mapper\Wrapper\ValidatedMapperCompiler;
use ShipMonk\InputMapper\Compiler\Type\PhpDocTypeUtils;
use ShipMonk\InputMapper\Compiler\Validator\Int\AssertIntRange;
use ShipMonk\InputMapper\Compiler\Validator\ValidatorCompiler;
use ShipMonk\InputMapper\Runtime\Optional;
use function class_exists;
use function class_implements;
use function class_parents;
use function count;
use function enum_exists;
use function interface_exists;
use function strtolower;
use function substr;

class DefaultMapperCompilerFactory implements MapperCompilerFactory
{

    /**
     * @param  array<class-string, callable(class-string, array<string, mixed>): MapperCompiler> $mapperCompilerFactories
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
     * @param  class-string<T>                                                 $className
     * @param  callable(class-string<T>, array<string, mixed>): MapperCompiler $factory
     */
    public function setMapperCompilerFactory(string $className, callable $factory): void
    {
        $this->mapperCompilerFactories[$className] = $factory; // @phpstan-ignore-line
    }

    /**
     * @param  array<string, mixed> $options
     */
    public function create(TypeNode $type, array $options): MapperCompiler
    {
        if ($type instanceof IdentifierTypeNode) {
            if (!PhpDocTypeUtils::isKeyword($type)) {
                if (!class_exists($type->name) && !interface_exists($type->name) && !enum_exists($type->name)) {
                    throw CannotInferMapperException::fromType($type);
                }

                $delegateObjectMapping = $options[self::DELEGATE_OBJECT_MAPPING] ?? true;
                return $delegateObjectMapping === true
                    ? new DelegateMapperCompiler($type->name)
                    : $this->createObjectMapperCompiler($type->name, [self::DELEGATE_OBJECT_MAPPING => false] + $options);
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
                    'negative-int' => new ValidatedMapperCompiler(new MapInt(), [new AssertIntRange(lt: 0)]),
                    'positive-int' => new ValidatedMapperCompiler(new MapInt(), [new AssertIntRange(gt: 0)]),
                    default => throw CannotInferMapperException::fromType($type),
                },
            };
        }

        if ($type instanceof NullableTypeNode) {
            return new MapNullable($this->create($type->type, $options));
        }

        if ($type instanceof GenericTypeNode) {
            return match (strtolower($type->type->name)) {
                'array' => match (count($type->genericTypes)) {
                    1 => new MapArray(new MapMixed(), $this->create($type->genericTypes[0], $options)),
                    2 => new MapArray($this->create($type->genericTypes[0], $options), $this->create($type->genericTypes[1], $options)),
                    default => throw CannotInferMapperException::fromType($type),
                },
                'int' => match (count($type->genericTypes)) {
                    2 => new ValidatedMapperCompiler(new MapInt(), [
                        new AssertIntRange(
                            gte: $this->resolveIntegerBoundary($type->genericTypes[0], 'min'),
                            lte: $this->resolveIntegerBoundary($type->genericTypes[1], 'max'),
                        ),
                    ]),
                    default => throw CannotInferMapperException::fromType($type),
                },
                default => match ($type->type->name) {
                    'list' => match (count($type->genericTypes)) {
                        1 => new MapList($this->create($type->genericTypes[0], $options)),
                        default => throw CannotInferMapperException::fromType($type),
                    },
                    Optional::class => match (count($type->genericTypes)) {
                        1 => new MapOptional($this->create($type->genericTypes[0], $options)),
                        default => throw CannotInferMapperException::fromType($type),
                    },
                    default => throw CannotInferMapperException::fromType($type),
                },
            };
        }

        if ($type instanceof ArrayTypeNode) {
            return new MapArray(new MapMixed(), $this->create($type->type, $options));
        }

        if ($type instanceof ArrayShapeNode) {
            $items = [];

            foreach ($type->items as $item) {
                $key = match (true) {
                    $item->keyName instanceof ConstExprStringNode => $item->keyName->value,
                    $item->keyName instanceof IdentifierTypeNode => $item->keyName->name,
                    default => throw CannotInferMapperException::fromType($type),
                };

                $items[] = new ArrayShapeItemMapping($key, $this->create($item->valueType, $options), $item->optional);
            }

            return new MapArrayShape($items, $type->sealed);
        }

        throw CannotInferMapperException::fromType($type);
    }

    /**
     * @param  class-string         $inputClassName
     * @param  array<string, mixed> $options
     */
    protected function createObjectMapperCompiler(string $inputClassName, array $options): MapperCompiler
    {
        $classLikeNames = [$inputClassName => true] + class_parents($inputClassName) + class_implements($inputClassName);

        foreach ($classLikeNames as $classLikeName => $_) {
            if (isset($this->mapperCompilerFactories[$classLikeName])) {
                $factory = $this->mapperCompilerFactories[$classLikeName];
                return $factory($inputClassName, $options);
            }
        }

        return $this->createObjectMappingByConstructorInvocation($inputClassName, $options);
    }

    /**
     * @param  class-string         $inputClassName
     * @param  array<string, mixed> $options
     */
    protected function createObjectMappingByConstructorInvocation(
        string $inputClassName,
        array $options,
    ): MapperCompiler
    {
        $classReflection = new ReflectionClass($inputClassName);

        $constructor = $classReflection->getConstructor();

        if ($constructor === null) {
            throw new LogicException("Class {$inputClassName} has no constructor");
        }

        if (!$constructor->isPublic()) {
            throw new LogicException("Class {$inputClassName} has a non-public constructor");
        }

        $constructorParameterMapperCompilers = [];
        $constructorParameterTypes = $this->getConstructorParameterTypes($constructor);

        foreach ($constructor->getParameters() as $parameter) {
            $name = $parameter->getName();
            $type = $constructorParameterTypes[$name];
            $constructorParameterMapperCompilers[$name] = $this->createParameterMapperCompiler($parameter, $type, $options);
        }

        return new MapObject($classReflection->getName(), $constructorParameterMapperCompilers);
    }

    /**
     * @return array<string, TypeNode>
     */
    protected function getConstructorParameterTypes(ReflectionMethod $constructor): array
    {
        $parameterTypes = [];

        foreach ($constructor->getParameters() as $parameter) {
            $parameterNativeType = $parameter->getType();
            $parameterType = $parameterNativeType !== null ? PhpDocTypeUtils::fromReflectionType($parameterNativeType) : new IdentifierTypeNode('mixed');
            $parameterName = $parameter->getName();
            $parameterTypes[$parameterName] = $parameterType;
        }

        $docComment = $constructor->getDocComment();

        if ($docComment !== false) {
            foreach ($this->parsePhpDoc($docComment)->children as $node) {
                if ($node instanceof PhpDocTagNode && $node->value instanceof ParamTagValueNode) {
                    PhpDocTypeUtils::resolve($node->value->type, $constructor->getDeclaringClass());
                    $parameterName = substr($node->value->parameterName, 1);
                    $parameterTypes[$parameterName] = $node->value->type;
                }
            }
        }

        return $parameterTypes;
    }

    /**
     * @param  array<string, mixed> $options
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

        if (count($mappers) === 0) {
            if ($type instanceof GenericTypeNode && $type->type->name === Optional::class) {
                $mappers[] = $this->create($type->genericTypes[0], $options);
            } else {
                $mappers[] = $this->create($type, $options);
            }
        }

        $mapper = count($mappers) > 1 ? new ChainMapperCompiler($mappers) : $mappers[0];
        $mapper = count($validators) > 0 ? new ValidatedMapperCompiler($mapper, $validators) : $mapper;

        if ($type instanceof GenericTypeNode && $type->type->name === Optional::class) {
            $mapper = new MapOptional($mapper);
        }

        return $mapper;
    }

    /**
     * @param  class-string<BackedEnum> $enumName
     * @param  array<string, mixed>     $options
     */
    protected function createEnumMapperCompiler(string $enumName, array $options): MapperCompiler
    {
        $enumReflection = new ReflectionEnum($enumName);
        $backingReflectionType = $enumReflection->getBackingType() ?? throw new LogicException("Enum {$enumName} has no backing type");
        $backingType = PhpDocTypeUtils::fromReflectionType($backingReflectionType);
        $backingTypeMapperCompiler = $this->create($backingType, $options);

        return new MapEnum($enumName, $backingTypeMapperCompiler);
    }

    /**
     * @param  class-string         $className
     * @param  array<string, mixed> $options
     */
    protected function createDateTimeMapperCompiler(string $className, array $options): MapperCompiler
    {
        if ($className === DateTimeInterface::class || $className === DateTimeImmutable::class) {
            return new MapDateTimeImmutable();
        }

        throw CannotInferMapperException::fromType(new IdentifierTypeNode($className));
    }

    protected function resolveIntegerBoundary(TypeNode $type, string $extremeName): ?int
    {
        if ($type instanceof ConstTypeNode && $type->constExpr instanceof ConstExprIntegerNode) {
            return (int) $type->constExpr->value;
        }

        if ($type instanceof IdentifierTypeNode && $type->name === $extremeName) {
            return null;
        }

        throw new LogicException("Unsupported integer boundary {$type}");
    }

    protected function parsePhpDoc(string $docComment): PhpDocNode
    {
        $tokens = $this->phpDocLexer->tokenize($docComment);
        return $this->phpDocParser->parse(new TokenIterator($tokens));
    }

}
