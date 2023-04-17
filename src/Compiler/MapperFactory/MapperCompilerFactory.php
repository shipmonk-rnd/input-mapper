<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\MapperFactory;

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
use ShipMonk\InputMapper\Compiler\Mapper\Object\MapObject;
use ShipMonk\InputMapper\Compiler\Mapper\Object\PropertyMapping;
use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapBool;
use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapFloat;
use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapInt;
use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapString;
use ShipMonk\InputMapper\Compiler\Mapper\Wrapper\ChainMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Wrapper\NullableMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Wrapper\OptionalMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Wrapper\ValidatedMapperCompiler;
use ShipMonk\InputMapper\Compiler\Type\PhpDocTypeUtils;
use ShipMonk\InputMapper\Compiler\Validator\Int\AssertIntRange;
use ShipMonk\InputMapper\Compiler\Validator\ValidatorCompiler;
use ShipMonk\InputMapper\Runtime\Optional;
use function class_exists;
use function count;
use function strtolower;
use function substr;

class MapperCompilerFactory
{

    public function __construct(
        private readonly Lexer $phpDocLexer,
        private readonly PhpDocParser $phpDocParser,
    )
    {
    }

    /**
     * @template T of object
     * @param class-string<T> $className
     * @return MapObject<T>
     */
    public function createObjectMapper(string $className): MapObject
    {
        $classReflection = new ReflectionClass($className);
        $mappings = [];

        $constructor = $classReflection->getConstructor();

        if ($constructor === null) {
            throw new LogicException("Class {$className} has no constructor");
        }

        $constructorParameterTypes = $this->getConstructorParameterTypes($constructor);

        foreach ($constructor->getParameters() as $parameter) {
            $name = $parameter->getName();
            $type = $constructorParameterTypes[$name];
            $mappings[] = $this->createPropertyMapping($name, $parameter, $type);
        }

        return new MapObject($classReflection->getName(), $mappings);
    }

    private function createPropertyMapping(
        string $name,
        ReflectionParameter $reflection,
        TypeNode $type
    ): PropertyMapping
    {
        $mappers = [];
        $validators = [];
        $optional = $type instanceof GenericTypeNode && $type->type->name === Optional::class;

        foreach ($reflection->getAttributes(MapperCompiler::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            $mappers[] = $attribute->newInstance();
        }

        foreach ($reflection->getAttributes(ValidatorCompiler::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            $validators[] = $attribute->newInstance();
        }

        if (count($mappers) === 0) {
            if ($type instanceof GenericTypeNode && $type->type->name === Optional::class) {
                $mappers[] = $this->inferMapperFromType($type->genericTypes[0]);
            } else {
                $mappers[] = $this->inferMapperFromType($type);
            }
        }

        $mapper = count($mappers) > 1 ? new ChainMapperCompiler($mappers) : $mappers[0];
        $mapper = count($validators) > 0 ? new ValidatedMapperCompiler($mapper, $validators) : $mapper;
        $mapper = $optional ? new OptionalMapperCompiler($mapper) : $mapper;

        return new PropertyMapping($name, $mapper, $optional);
    }

    private function inferMapperFromType(TypeNode $type): MapperCompiler
    {
        if ($type instanceof IdentifierTypeNode) {
            if (!PhpDocTypeUtils::isKeyword($type) && class_exists($type->name)) {
                return new DelegateMapperCompiler($type->name);
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
            return new NullableMapperCompiler($this->inferMapperFromType($type->type));
        }

        if ($type instanceof GenericTypeNode) {
            return match (strtolower($type->type->name)) {
                'array' => match (count($type->genericTypes)) {
                    1 => new MapArray(new MapMixed(), $this->inferMapperFromType($type->genericTypes[0])),
                    2 => new MapArray($this->inferMapperFromType($type->genericTypes[0]), $this->inferMapperFromType($type->genericTypes[1])),
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
                'list' => match (count($type->genericTypes)) {
                    1 => new MapList($this->inferMapperFromType($type->genericTypes[0])),
                    default => throw CannotInferMapperException::fromType($type),
                },
                default => throw CannotInferMapperException::fromType($type),
            };
        }

        if ($type instanceof ArrayTypeNode) {
            return new MapArray(new MapMixed(), $this->inferMapperFromType($type->type));
        }

        if ($type instanceof ArrayShapeNode) {
            $items = [];

            foreach ($type->items as $item) {
                $key = match (true) {
                    $item->keyName instanceof ConstExprStringNode => $item->keyName->value,
                    $item->keyName instanceof IdentifierTypeNode => $item->keyName->name,
                    default => throw CannotInferMapperException::fromType($type),
                };

                $items[] = new ArrayShapeItemMapping($key, $this->inferMapperFromType($item->valueType), $item->optional);
            }

            return new MapArrayShape($items, $type->sealed);
        }

        throw CannotInferMapperException::fromType($type);
    }

    /**
     * @return array<string, TypeNode>
     */
    private function getConstructorParameterTypes(ReflectionMethod $constructor): array
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

    private function resolveIntegerBoundary(TypeNode $type, string $extremeName): ?int
    {
        if ($type instanceof ConstTypeNode && $type->constExpr instanceof ConstExprIntegerNode) {
            return (int) $type->constExpr->value;
        }

        if ($type instanceof IdentifierTypeNode && $type->name === $extremeName) {
            return null;
        }

        throw new LogicException("Unsupported integer boundary {$type}");
    }

    private function parsePhpDoc(string $docComment): PhpDocNode
    {
        $tokens = $this->phpDocLexer->tokenize($docComment);
        return $this->phpDocParser->parse(new TokenIterator($tokens));
    }

}
