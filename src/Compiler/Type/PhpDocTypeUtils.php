<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Type;

use LogicException;
use Nette\Utils\Arrays;
use Nette\Utils\Reflection;
use Nette\Utils\Validators;
use PhpParser\Node\ComplexType;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprFalseNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprFloatNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprIntegerNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprNullNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprStringNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprTrueNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstFetchNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeItemNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
use PHPStan\PhpDocParser\Ast\Type\CallableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ConstTypeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IntersectionTypeNode;
use PHPStan\PhpDocParser\Ast\Type\NullableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ObjectShapeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use ReflectionClass;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;
use ShipMonk\InputMapper\Runtime\Optional;
use ShipMonk\InputMapper\Runtime\OptionalNone;
use ShipMonk\InputMapper\Runtime\OptionalSome;
use Traversable;
use function array_map;
use function array_shift;
use function array_splice;
use function array_values;
use function constant;
use function count;
use function get_object_vars;
use function in_array;
use function is_a;
use function is_array;
use function is_callable;
use function is_float;
use function is_int;
use function is_object;
use function is_string;
use function max;
use function method_exists;
use function str_contains;
use function strcasecmp;
use function strtolower;
use const PHP_INT_MAX;
use const PHP_INT_MIN;

class PhpDocTypeUtils
{

    private const NATIVE_KEYWORDS = [
        'array' => true,
        'bool' => true,
        'callable' => true,
        'false' => true,
        'float' => true,
        'int' => true,
        'iterable' => true,
        'mixed' => true,
        'never' => true,
        'null' => true,
        'object' => true,
        'parent' => true,
        'self' => true,
        'static' => true,
        'string' => true,
        'true' => true,
        'void' => true,
    ];

    private const KEYWORDS = self::NATIVE_KEYWORDS + [
        'boolean' => true,
        'double' => true,
        'empty' => true,
        'integer' => true,
        'list' => true,
        'max' => true,
        'min' => true,
        'noreturn' => true,
        'number' => true,
        'numeric' => true,
        'resource' => true,
        'scalar' => true,
    ];

    public static function isKeyword(IdentifierTypeNode $type): bool
    {
        return isset(self::KEYWORDS[$type->name])
            || isset(self::NATIVE_KEYWORDS[strtolower($type->name)])
            || str_contains($type->name, '-');
    }

    public static function isMixed(TypeNode $type): bool
    {
        return $type instanceof IdentifierTypeNode && strtolower($type->name) === 'mixed';
    }

    public static function isNull(TypeNode $type): bool
    {
        return $type instanceof IdentifierTypeNode && strtolower($type->name) === 'null';
    }

    public static function fromReflectionType(ReflectionType $reflectionType): TypeNode
    {
        if ($reflectionType instanceof ReflectionNamedType) {
            $type = new IdentifierTypeNode($reflectionType->getName());
            return $reflectionType->allowsNull() && strtolower($type->name) !== 'null' ? new NullableTypeNode($type) : $type;
        }

        if ($reflectionType instanceof ReflectionUnionType) {
            return new UnionTypeNode(array_map(self::fromReflectionType(...), $reflectionType->getTypes()));
        }

        if ($reflectionType instanceof ReflectionIntersectionType) {
            return new IntersectionTypeNode(array_map(self::fromReflectionType(...), $reflectionType->getTypes()));
        }

        return new IdentifierTypeNode('mixed');
    }

    public static function toNativeType(TypeNode $type, ?bool &$phpDocUseful): ComplexType|Identifier|Name
    {
        if ($phpDocUseful === null) {
            $phpDocUseful = false;
        }

        if ($type instanceof IdentifierTypeNode) {
            if (!self::isKeyword($type)) {
                return new Name($type->name);
            }

            if (isset(self::NATIVE_KEYWORDS[$type->name])) {
                return new Identifier($type->name);
            }

            $phpDocUseful = true;
            return match ($type->name) {
                'list',
                'non-empty-list' => new Identifier('array'),
                'positive-int',
                'negative-int',
                'non-positive-int',
                'non-negative-int' => new Identifier('int'),
                default => new Identifier('mixed'),
            };
        }

        if ($type instanceof NullableTypeNode) {
            return NativeTypeUtils::createNullable(self::toNativeType($type->type, $phpDocUseful));
        }

        if ($type instanceof ArrayTypeNode || $type instanceof ArrayShapeNode) {
            $phpDocUseful = true;
            return new Identifier('array');
        }

        if ($type instanceof CallableTypeNode) {
            $phpDocUseful = true;
            return new Identifier('callable');
        }

        if ($type instanceof ObjectShapeNode) {
            $phpDocUseful = true;
            return new Identifier('object');
        }

        if ($type instanceof GenericTypeNode) {
            $phpDocUseful = true;
            return self::toNativeType($type->type, $phpDocUseful);
        }

        if ($type instanceof UnionTypeNode) {
            $types = [];

            foreach ($type->types as $inner) {
                $types[] = self::toNativeType($inner, $phpDocUseful);
            }

            return NativeTypeUtils::createUnion(...$types);
        }

        if ($type instanceof IntersectionTypeNode) {
            $types = [];

            foreach ($type->types as $inner) {
                $types[] = self::toNativeType($inner, $phpDocUseful);
            }

            return NativeTypeUtils::createIntersection(...$types);
        }

        $phpDocUseful = true;
        return new Identifier('mixed');
    }

    public static function isNullable(TypeNode $type): bool
    {
        if ($type instanceof IdentifierTypeNode && ($type->name === 'null' || $type->name === 'mixed')) {
            return true;
        }

        if ($type instanceof NullableTypeNode) {
            return true;
        }

        if ($type instanceof UnionTypeNode) {
            return Arrays::some($type->types, self::isNullable(...));
        }

        return false;
    }

    public static function makeNullable(TypeNode $type): TypeNode
    {
        if (self::isNullable($type)) {
            return $type;
        }

        if ($type instanceof UnionTypeNode) {
            return new UnionTypeNode([...$type->types, new IdentifierTypeNode('null')]);
        }

        return new NullableTypeNode($type);
    }

    /**
     * @param ReflectionClass<object> $context
     */
    public static function resolve(mixed $type, ReflectionClass $context): void
    {
        if (is_array($type)) {
            foreach ($type as $item) {
                self::resolve($item, $context);
            }
        } elseif ($type instanceof IdentifierTypeNode) {
            if (!self::isKeyword($type) || $type->name === 'self' || $type->name === 'static' || $type->name === 'parent') {
                $type->name = Reflection::expandClassName($type->name, $context);
            }
        } elseif ($type instanceof ArrayShapeItemNode) {
            self::resolve($type->valueType, $context); // intentionally not resolving key type

        } elseif (is_object($type)) {
            foreach (get_object_vars($type) as $item) {
                self::resolve($item, $context);
            }
        }
    }

    public static function union(TypeNode ...$types): TypeNode
    {
        for ($i = 0; $i < count($types); $i++) {
            for ($j = $i + 1; $j < count($types); $j++) {
                if (self::isSubTypeOf($types[$i], $types[$j])) {
                    array_splice($types, $i--, 1);
                    continue 2;
                }

                if (self::isSubTypeOf($types[$j], $types[$i])) {
                    array_splice($types, $j--, 1);
                    continue;
                }
            }
        }

        return match (count($types)) {
            0 => new IdentifierTypeNode('never'),
            1 => $types[0],
            default => new UnionTypeNode($types),
        };
    }

    public static function intersect(TypeNode ...$types): TypeNode
    {
        for ($i = 0; $i < count($types); $i++) {
            for ($j = 0; $j < count($types); $j++) {
                if ($i === $j) {
                    continue;
                }

                $a = $types[$i];
                $b = $types[$j];

                if (self::isSubTypeOf($b, $a)) {
                    array_splice($types, $i--, 1);
                    continue 2;
                }

                if ($b instanceof IdentifierTypeNode) {
                    $b = new GenericTypeNode($b, []); // @phpstan-ignore-line intentionally converting to generic type
                }

                if (
                    $a instanceof GenericTypeNode
                    && $b instanceof GenericTypeNode
                    && self::isSubTypeOf($b->type, $a->type)
                ) {
                    $typeDef = self::getGenericTypeDefinition($b);
                    $downCastedType = self::downCast($a, $b->type->name);

                    $intersectedParameters = [];
                    $intersectedParameterMapping = [];
                    $intersectedParameterCount = max(count($b->genericTypes), count($downCastedType->genericTypes));

                    foreach ($typeDef['parameters'] ?? [] as $parameterIndex => $parameterDef) {
                        if (!isset($parameterDef['index'])) {
                            $intersectedParameterMapping[$parameterIndex] = $parameterIndex;

                        } elseif (isset($parameterDef['index'][$intersectedParameterCount])) {
                            $intersectedParameterIndex = $parameterDef['index'][$intersectedParameterCount];
                            $intersectedParameterMapping[$intersectedParameterIndex] = $parameterIndex;
                        }
                    }

                    for ($k = 0; $k < $intersectedParameterCount; $k++) {
                        if (!isset($intersectedParameterMapping[$k])) {
                            throw new LogicException('Invalid generic type definition');
                        }

                        $intersectedParameters[$k] = self::intersect(
                            self::getGenericTypeParameter($downCastedType, $intersectedParameterMapping[$k]),
                            self::getGenericTypeParameter($b, $intersectedParameterMapping[$k]),
                        );
                    }

                    $types[$j] = new GenericTypeNode($b->type, $intersectedParameters);
                    array_splice($types, $i--, 1);
                    continue 2;
                }
            }
        }

        return match (count($types)) {
            0 => new IdentifierTypeNode('mixed'),
            1 => $types[0],
            default => new IntersectionTypeNode($types),
        };
    }

    /**
     * Returns true if $a is subtype of $b.
     */
    public static function isSubTypeOf(TypeNode $a, TypeNode $b): bool
    {
        // normalize types
        $a = self::normalizeType($a);
        $b = self::normalizeType($b);

        // universal subtype
        if ($a instanceof IdentifierTypeNode && $a->name === 'never') {
            return true;
        }

        // expand complex types
        if ($a instanceof UnionTypeNode) {
            return Arrays::every($a->types, static fn(TypeNode $inner) => self::isSubTypeOf($inner, $b));
        }

        if ($b instanceof UnionTypeNode) {
            return Arrays::some($b->types, static fn(TypeNode $inner) => self::isSubTypeOf($a, $inner));
        }

        if ($a instanceof IntersectionTypeNode) {
            return Arrays::every($a->types, static fn(TypeNode $inner) => self::isSubTypeOf($inner, $b));
        }

        if ($b instanceof IntersectionTypeNode) {
            return Arrays::some($b->types, static fn(TypeNode $inner) => self::isSubTypeOf($a, $inner));
        }

        if ($b instanceof IdentifierTypeNode) {
            if (!self::isKeyword($b)) {
                return match (true) {
                    $a instanceof IdentifierTypeNode => is_a($a->name, $b->name, true),
                    $a instanceof GenericTypeNode => is_a($a->type->name, $b->name, true),
                    default => false,
                };
            }

            return match ($b->name) {
                'array' => match (true) {
                    $a instanceof ArrayTypeNode => true,
                    $a instanceof ArrayShapeNode => true,
                    $a instanceof IdentifierTypeNode => in_array($a->name, ['array', 'list', 'non-empty-list'], true),
                    $a instanceof GenericTypeNode => in_array($a->type->name, ['array', 'list', 'non-empty-list'], true),
                    default => false,
                },

                'callable' => match (true) {
                    $a instanceof CallableTypeNode => true,
                    $a instanceof IdentifierTypeNode => self::isKeyword($a) ? $a->name === 'callable' : method_exists($a->name, '__invoke'),
                    $a instanceof ConstTypeNode => match (true) {
                        $a->constExpr instanceof ConstExprStringNode => is_callable($a->constExpr->value),
                        $a->constExpr instanceof ConstFetchNode => is_callable(constant((string) $a->constExpr)),
                        default => false,
                    },
                    default => false,
                },

                'false' => match (true) {
                    $a instanceof IdentifierTypeNode => $a->name === 'false',
                    $a instanceof ConstTypeNode => match (true) {
                        $a->constExpr instanceof ConstExprFalseNode => true,
                        $a->constExpr instanceof ConstFetchNode => constant((string) $a->constExpr) === false,
                        default => false,
                    },
                    default => false,
                },

                'float' => match (true) {
                    $a instanceof IdentifierTypeNode => $a->name === 'float',
                    $a instanceof ConstTypeNode => match (true) {
                        $a->constExpr instanceof ConstExprFloatNode => true,
                        $a->constExpr instanceof ConstFetchNode => is_float(constant((string) $a->constExpr)),
                        default => false,
                    },
                    default => false,
                },

                'int' => match (true) {
                    $a instanceof IdentifierTypeNode => $a->name === 'int',
                    $a instanceof GenericTypeNode => $a->type->name === 'int',
                    $a instanceof ConstTypeNode => match (true) {
                        $a->constExpr instanceof ConstExprIntegerNode => true,
                        $a->constExpr instanceof ConstFetchNode => is_int(constant((string) $a->constExpr)),
                        default => false,
                    },
                    default => false,
                },

                'list' => match (true) {
                    $a instanceof ArrayShapeNode => Arrays::every($a->items, static fn(ArrayShapeItemNode $item, int $idx) => self::getArrayShapeKey($item) === (string) $idx),
                    $a instanceof IdentifierTypeNode => $a->name === 'list' || $a->name === 'non-empty-list',
                    $a instanceof GenericTypeNode => $a->type->name === 'list' || $a->type->name === 'non-empty-list',
                    default => false,
                },

                'mixed' => true,

                'never' => $a instanceof IdentifierTypeNode && $a->name === 'never',

                'non-empty-list' => match (true) {
                    $a instanceof ArrayShapeNode => Arrays::every($a->items, static fn(ArrayShapeItemNode $item, int $idx) => self::getArrayShapeKey($item) === (string) $idx)
                        && Arrays::some($a->items, static fn(ArrayShapeItemNode $item, int $idx) => !$item->optional),
                    $a instanceof IdentifierTypeNode => $a->name === 'non-empty-list',
                    $a instanceof GenericTypeNode => $a->type->name === 'non-empty-list',
                    default => false,
                },

                'null' => match (true) {
                    $a instanceof IdentifierTypeNode => $a->name === 'null',
                    $a instanceof ConstTypeNode => match (true) {
                        $a->constExpr instanceof ConstExprNullNode => true,
                        $a->constExpr instanceof ConstFetchNode => constant((string) $a->constExpr) === null,
                        default => false,
                    },
                    default => false,
                },

                'object' => match (true) {
                    $a instanceof ObjectShapeNode => true,
                    $a instanceof IdentifierTypeNode => $a->name === 'object' || !self::isKeyword($a),
                    $a instanceof GenericTypeNode => !self::isKeyword($a->type),
                    default => false,
                },

                'resource' => $a instanceof IdentifierTypeNode && $a->name === 'resource',

                'string' => match (true) {
                    $a instanceof IdentifierTypeNode => $a->name === 'string',
                    $a instanceof ConstTypeNode => match (true) {
                        $a->constExpr instanceof ConstExprStringNode => true,
                        $a->constExpr instanceof ConstFetchNode => is_string(constant((string) $a->constExpr)),
                        default => false,
                    },
                    default => false,
                },

                'true' => match (true) {
                    $a instanceof IdentifierTypeNode => $a->name === 'true',
                    $a instanceof ConstTypeNode => match (true) {
                        $a->constExpr instanceof ConstExprTrueNode => true,
                        $a->constExpr instanceof ConstFetchNode => constant((string) $a->constExpr) === true,
                        default => false,
                    },
                    default => false,
                },

                'void' => $a instanceof IdentifierTypeNode && $a->name === 'void',

                default => false,
            };
        }

        if ($b instanceof GenericTypeNode) {
            if ($b->type->name === 'int' && count($b->genericTypes) === 2) {
                $bLowerBound = self::resolveIntegerBoundary($b->genericTypes[0], 'min', PHP_INT_MIN);
                $bUpperBound = self::resolveIntegerBoundary($b->genericTypes[1], 'max', PHP_INT_MAX);

                if ($a instanceof GenericTypeNode && count($a->genericTypes) === 2) {
                    $aLowerBound = self::resolveIntegerBoundary($a->genericTypes[0], 'min', PHP_INT_MIN);
                    $aUpperBound = self::resolveIntegerBoundary($a->genericTypes[1], 'max', PHP_INT_MAX);

                } elseif ($a instanceof ConstTypeNode) {
                    if ($a->constExpr instanceof ConstExprIntegerNode) {
                        $bound = (int) $a->constExpr->value;
                        $aLowerBound = $bound;
                        $aUpperBound = $bound;
                    } elseif ($a->constExpr instanceof ConstFetchNode) {
                        $bound = constant((string) $a->constExpr);

                        if (!is_int($bound)) {
                            return false;
                        }

                        $aLowerBound = $bound;
                        $aUpperBound = $bound;

                    } else {
                        return false;
                    }
                } else {
                    return false;
                }

                return $aLowerBound >= $bLowerBound && $aUpperBound <= $bUpperBound;
            }

            return match (true) {
                $a instanceof GenericTypeNode => self::isSubTypeOfGeneric($a, $b),
                $a instanceof IdentifierTypeNode => self::isSubTypeOfGeneric(new GenericTypeNode($a, []), $b),
                $a instanceof ArrayShapeNode => self::isSubTypeOfGeneric(self::convertArrayShapeToGenericType($a), $b),
                default => false,
            };
        }

        if ($b instanceof ArrayShapeNode) {
            if (!$a instanceof ArrayShapeNode) {
                return false;
            }

            $aItemsByKey = [];

            foreach ($a->items as $item) {
                $aItemsByKey[self::getArrayShapeKey($item)] = $item;
            }

            foreach ($b->items as $bItem) {
                $bKey = self::getArrayShapeKey($bItem);
                $aItem = $aItemsByKey[$bKey] ?? null;

                if ($aItem !== null) {
                    unset($aItemsByKey[$bKey]);

                    if (!self::isSubTypeOf($aItem->valueType, $bItem->valueType) || $aItem->optional !== $bItem->optional) {
                        return false;
                    }
                } elseif (!$bItem->optional) {
                    return false;
                }
            }

            return !$b->sealed || ($a->sealed && count($aItemsByKey) <= 0);
        }

        return false;
    }

    public static function inferGenericParameter(TypeNode $type, string $typeName, int $parameter): TypeNode
    {
        $type = self::normalizeType($type);

        if ($type instanceof UnionTypeNode) {
            return self::union(...Arrays::map(
                $type->types,
                static fn(TypeNode $type) => self::inferGenericParameter($type, $typeName, $parameter),
            ));
        }

        if ($type instanceof IntersectionTypeNode) {
            return self::intersect(...Arrays::map(
                $type->types,
                static fn(TypeNode $type) => self::inferGenericParameter($type, $typeName, $parameter),
            ));
        }

        if ($type instanceof GenericTypeNode) {
            if (strcasecmp($type->type->name, $typeName) === 0) {
                return self::getGenericTypeParameter($type, $parameter);
            }

            $superTypes = array_values(self::getGenericTypeSuperTypes($type));

            if (count($superTypes) > 0) {
                return self::union(...Arrays::map(
                    $superTypes,
                    static fn(TypeNode $superType) => self::inferGenericParameter($superType, $typeName, $parameter),
                ));
            }
        }

        throw new LogicException("Unable to infer generic parameter, {$type} is not subtype of {$typeName}");
    }

    private static function downCast(GenericTypeNode $type, string $targetTypeName): GenericTypeNode
    {
        $path = self::findDownCastPath($type->type->name, $targetTypeName);

        if ($path === null) {
            throw new LogicException("Unable to downcast {$type->type->name} to {$targetTypeName}");
        }

        return self::downCastOverPath($type, $path);
    }

    /**
     * @return list<string>|null
     */
    private static function findDownCastPath(string $sourceTypeName, string $targetTypeName): ?array
    {
        if ($sourceTypeName === $targetTypeName) {
            return [];
        }

        $targetTypeDef = self::getGenericTypeDefinition(new GenericTypeNode(new IdentifierTypeNode($targetTypeName), []));

        foreach ($targetTypeDef['extends'] ?? [] as $possibleTarget => $_) {
            $innerPath = self::findDownCastPath($sourceTypeName, $possibleTarget);

            if ($innerPath !== null) {
                return [...$innerPath, $targetTypeName];
            }
        }

        return null;
    }

    /**
     * @param  list<string> $path
     */
    private static function downCastOverPath(GenericTypeNode $type, array $path): GenericTypeNode
    {
        if (count($path) === 0) {
            return $type;
        }

        $step = array_shift($path);
        $targetTypeDef = self::getGenericTypeDefinition(new GenericTypeNode(new IdentifierTypeNode($step), []));

        if (!isset($targetTypeDef['extends'][$type->type->name])) {
            throw new LogicException('Invalid downcast path');
        }

        $targetTypeParameters = Arrays::map($targetTypeDef['parameters'] ?? [], static function (array $parameter): TypeNode {
            return $parameter['bound'] ?? new IdentifierTypeNode('mixed');
        });

        foreach ($targetTypeDef['extends'][$type->type->name] as $sourceIndex => $typeOrIndex) {
            if (is_int($typeOrIndex)) {
                $targetTypeParameters[$typeOrIndex] = self::getGenericTypeParameter($type, $sourceIndex);
            }
        }

        return self::downCastOverPath(new GenericTypeNode(new IdentifierTypeNode($step), $targetTypeParameters), $path);
    }

    private static function isSubTypeOfGeneric(GenericTypeNode $a, GenericTypeNode $b): bool
    {
        if (strcasecmp($a->type->name, $b->type->name) === 0) {
            $typeDef = self::getGenericTypeDefinition($a);
            return Arrays::every($typeDef['parameters'] ?? [], static function (array $parameter, int $idx) use ($a, $b): bool {
                $genericTypeA = self::getGenericTypeParameter($a, $idx);
                $genericTypeB = self::getGenericTypeParameter($b, $idx);

                return match ($parameter['variance']) {
                    'in' => self::isSubTypeOf($genericTypeB, $genericTypeA),
                    'out' => self::isSubTypeOf($genericTypeA, $genericTypeB),
                    'inout' => self::isSubTypeOf($genericTypeA, $genericTypeB) && self::isSubTypeOf($genericTypeB, $genericTypeA),
                    default => throw new LogicException("Invalid variance {$parameter['variance']}"),
                };
            });
        }

        return Arrays::some(self::getGenericTypeSuperTypes($a), static function (GenericTypeNode $superType) use ($b): bool {
            return self::isSubTypeOf($superType, $b);
        });
    }

    /**
     * @return array<string, GenericTypeNode>
     */
    public static function getGenericTypeSuperTypes(GenericTypeNode $type): array
    {
        $typeDef = self::getGenericTypeDefinition($type);

        return Arrays::map($typeDef['extends'] ?? [], static function (array $mapping, string $superTypeName) use ($type): GenericTypeNode {
            return new GenericTypeNode(new IdentifierTypeNode($superTypeName), Arrays::map($mapping, static function (TypeNode | int $typeOrIndex) use ($type): TypeNode {
                return $typeOrIndex instanceof TypeNode ? $typeOrIndex : self::getGenericTypeParameter($type, $typeOrIndex);
            }));
        });
    }

    private static function getGenericTypeParameter(GenericTypeNode $type, int $index): TypeNode
    {
        $typeDef = self::getGenericTypeDefinition($type);

        if (!isset($typeDef['parameters'])) {
            throw new LogicException('Generic type has no parameters');
        }

        $parameterDef = $typeDef['parameters'][$index];
        $count = count($type->genericTypes);

        if (isset($parameterDef['index'])) {
            $index = $parameterDef['index'][$count] ?? -1;
        }

        return $type->genericTypes[$index] ?? $parameterDef['bound'] ?? new IdentifierTypeNode('mixed');
    }

    /**
     * @return array{
     *     extends?: array<string, list<int | TypeNode>>,
     *     parameters?: list<array{index?: array<int, int>, variance: 'in' | 'out' | 'inout', bound?: TypeNode}>,
     * }
     */
    private static function getGenericTypeDefinition(GenericTypeNode $type): array
    {
        return match ($type->type->name) {
            'array' => [
                'extends' => [
                    'iterable' => [0, 1],
                ],
                'parameters' => [
                    ['index' => [2 => 0], 'variance' => 'out', 'bound' => new UnionTypeNode([new IdentifierTypeNode('int'), new IdentifierTypeNode('string')])],
                    ['index' => [1 => 0, 2 => 1], 'variance' => 'out'],
                ],
            ],

            'list' => [
                'extends' => [
                    'array' => [new IdentifierTypeNode('int'), 0],
                ],
                'parameters' => [
                    ['variance' => 'out'],
                ],
            ],

            'iterable' => [
                'parameters' => [
                    ['index' => [2 => 0], 'variance' => 'out'],
                    ['index' => [1 => 0, 2 => 1], 'variance' => 'out'],
                ],
            ],

            'non-empty-list' => [
                'extends' => [
                    'list' => [0],
                ],
                'parameters' => [
                    ['variance' => 'out'],
                ],
            ],

            Optional::class => [
                'parameters' => [
                    ['variance' => 'out'],
                ],
            ],

            OptionalSome::class => [
                'extends' => [
                    Optional::class => [0],
                ],
                'parameters' => [
                    ['variance' => 'out'],
                ],
            ],

            OptionalNone::class => [
                'extends' => [
                    Optional::class => [new IdentifierTypeNode('never')],
                ],
            ],

            default => [],
        };
    }

    private static function convertArrayShapeToGenericType(ArrayShapeNode $type): GenericTypeNode
    {
        $valueType = new UnionTypeNode(Arrays::map($type->items, static fn(ArrayShapeItemNode $item) => $item->valueType));

        if (Arrays::every($type->items, static fn(ArrayShapeItemNode $item, int $idx) => self::getArrayShapeKey($item) === (string) $idx)) {
            return new GenericTypeNode(new IdentifierTypeNode('list'), [$valueType]);
        }

        $keyType = new UnionTypeNode(Arrays::map($type->items, self::getArrayShapeKeyType(...)));
        return new GenericTypeNode(new IdentifierTypeNode('array'), [$keyType, $valueType]);
    }

    private static function getArrayShapeKey(ArrayShapeItemNode $item): string
    {
        if ($item->keyName instanceof ConstExprStringNode || $item->keyName instanceof ConstExprIntegerNode) {
            return $item->keyName->value;
        }

        throw new LogicException('Invalid array shape key');
    }

    private static function getArrayShapeKeyType(ArrayShapeItemNode $item): TypeNode
    {
        if ($item->keyName instanceof ConstExprStringNode || $item->keyName instanceof ConstExprIntegerNode) {
            return new ConstTypeNode($item->keyName);
        }

        throw new LogicException('Invalid array shape key');
    }

    private static function normalizeType(TypeNode $type): TypeNode
    {
        if ($type instanceof IdentifierTypeNode) {
            return match (strtolower($type->name)) {
                'bool', 'boolean' => new UnionTypeNode([
                    new IdentifierTypeNode('true'),
                    new IdentifierTypeNode('false'),
                ]),
                'double' => new IdentifierTypeNode('float'),
                'integer' => new IdentifierTypeNode('int'),
                'iterable' => new UnionTypeNode([
                    new IdentifierTypeNode('array'),
                    new IdentifierTypeNode(Traversable::class),
                ]),
                'negative-int' => new GenericTypeNode(new IdentifierTypeNode('int'), [
                    new IdentifierTypeNode('min'),
                    new ConstTypeNode(new ConstExprIntegerNode('-1')),
                ]),
                'non-negative-int' => new GenericTypeNode(new IdentifierTypeNode('int'), [
                    new ConstTypeNode(new ConstExprIntegerNode('0')),
                    new IdentifierTypeNode('max'),
                ]),
                'non-positive-int' => new GenericTypeNode(new IdentifierTypeNode('int'), [
                    new IdentifierTypeNode('min'),
                    new ConstTypeNode(new ConstExprIntegerNode('0')),
                ]),
                'noreturn' => new IdentifierTypeNode('never'),
                'number' => new UnionTypeNode([new IdentifierTypeNode('int'), new IdentifierTypeNode('float')]),
                'positive-int' => new GenericTypeNode(new IdentifierTypeNode('int'), [
                    new ConstTypeNode(new ConstExprIntegerNode('1')),
                    new IdentifierTypeNode('max'),
                ]),
                'scalar' => new UnionTypeNode([
                    new IdentifierTypeNode('int'),
                    new IdentifierTypeNode('float'),
                    new IdentifierTypeNode('string'),
                    new IdentifierTypeNode('bool'),
                ]),
                default => self::isKeyword($type) ? new IdentifierTypeNode(strtolower($type->name)) : $type,
            };
        }

        if ($type instanceof NullableTypeNode) {
            return new UnionTypeNode([new IdentifierTypeNode('null'), self::normalizeType($type->type)]);
        }

        if ($type instanceof ArrayTypeNode) {
            return new GenericTypeNode(new IdentifierTypeNode('array'), [
                new UnionTypeNode([new IdentifierTypeNode('int'), new IdentifierTypeNode('string')]),
                self::normalizeType($type->type),
            ]);
        }

        if ($type instanceof ArrayShapeNode) {
            $newItems = [];
            $newAutoIndex = 0;

            foreach ($type->items as $item) {
                if ($item->keyName === null) {
                    $newItems[] = new ArrayShapeItemNode(
                        keyName: new ConstExprIntegerNode((string) ($newAutoIndex++)),
                        optional: $item->optional,
                        valueType: $item->valueType,
                    );

                } elseif ($item->keyName instanceof ConstExprIntegerNode) {
                    $newAutoIndex = max($newAutoIndex, ((int) ($item->keyName->value)) + 1);
                    $newItems[] = $item;

                } elseif ($item->keyName instanceof ConstExprStringNode && Validators::isNumericInt($item->keyName->value)) {
                    $newAutoIndex = max($newAutoIndex, ((int) ($item->keyName->value)) + 1);
                    $newItems[] = new ArrayShapeItemNode(
                        keyName: new ConstExprIntegerNode($item->keyName->value),
                        optional: $item->optional,
                        valueType: $item->valueType,
                    );

                } elseif ($item->keyName instanceof IdentifierTypeNode) {
                    $newItems[] = new ArrayShapeItemNode(
                        keyName: new ConstExprStringNode($item->keyName->name),
                        optional: $item->optional,
                        valueType: $item->valueType,
                    );

                } else {
                    $newItems[] = $item;
                }
            }

            return new ArrayShapeNode($newItems, $type->sealed, $type->kind);
        }

        if ($type instanceof GenericTypeNode) {
            if (
                strtolower($type->type->name) === 'int'
                && count($type->genericTypes) === 2
                && $type->genericTypes[0] instanceof IdentifierTypeNode
                && $type->genericTypes[1] instanceof IdentifierTypeNode
                && strtolower($type->genericTypes[0]->name) === 'min'
                && strtolower($type->genericTypes[1]->name) === 'max'
            ) {
                return new IdentifierTypeNode('int');
            }

            if (self::isKeyword($type->type)) {
                return new GenericTypeNode(new IdentifierTypeNode(strtolower($type->type->name)), $type->genericTypes);
            }
        }

        return $type;
    }

    private static function resolveIntegerBoundary(TypeNode $boundaryType, string $extremeName, int $extremeValue): int
    {
        if ($boundaryType instanceof ConstTypeNode && $boundaryType->constExpr instanceof ConstExprIntegerNode) {
            return (int) $boundaryType->constExpr->value;
        }

        if ($boundaryType instanceof IdentifierTypeNode && $boundaryType->name === $extremeName) {
            return $extremeValue;
        }

        throw new LogicException('Invalid integer boundary type');
    }

}
