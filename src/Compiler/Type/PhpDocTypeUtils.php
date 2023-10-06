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
use function array_splice;
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
                'list' => new Identifier('array'),
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
            for ($j = $i + 1; $j < count($types); $j++) {
                if (self::isSubTypeOf($types[$i], $types[$j])) {
                    array_splice($types, $j--, 1);
                    continue;
                }

                if (self::isSubTypeOf($types[$j], $types[$i])) {
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
                    $a instanceof IdentifierTypeNode => in_array($a->name, ['array', 'list'], true),
                    $a instanceof GenericTypeNode => in_array($a->type->name, ['array', 'list'], true),
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
                    $a instanceof ConstTypeNode => match (true) {
                        $a->constExpr instanceof ConstExprIntegerNode => true,
                        $a->constExpr instanceof ConstFetchNode => is_int(constant((string) $a->constExpr)),
                        default => false,
                    },
                    default => false,
                },

                'list' => match (true) {
                    $a instanceof ArrayShapeNode => Arrays::every($a->items, static fn(ArrayShapeItemNode $item, int $idx) => self::getArrayShapeKey($item) === (string) $idx),
                    $a instanceof IdentifierTypeNode => $a->name === 'list',
                    $a instanceof GenericTypeNode => $a->type->name === 'list',
                    default => false,
                },

                'mixed' => true,

                'never' => $a instanceof IdentifierTypeNode && $a->name === 'never',

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
            $typeDef = self::getGenericTypeDefinition($type);

            if (strcasecmp($type->type->name, $typeName) === 0) {
                return $type->genericTypes[$parameter] ?? $typeDef['parameters'][$parameter]['bound'] ?? new IdentifierTypeNode('mixed');
            }

            $superTypes = isset($typeDef['superTypes']) ? $typeDef['superTypes']($type->genericTypes) : [];

            if (count($superTypes) > 0) {
                return self::union(...Arrays::map(
                    $superTypes,
                    static fn(TypeNode $superType) => self::inferGenericParameter($superType, $typeName, $parameter),
                ));
            }
        }

        throw new LogicException("Unable to infer generic parameter, {$type} is not subtype of {$typeName}");
    }

    private static function isSubTypeOfGeneric(GenericTypeNode $a, GenericTypeNode $b): bool
    {
        $typeDef = self::getGenericTypeDefinition($a);

        if (strcasecmp($a->type->name, $b->type->name) === 0) {
            return Arrays::every($typeDef['parameters'] ?? [], static function (array $parameter, int $idx) use ($a, $b): bool {
                $genericTypeA = $a->genericTypes[$idx] ?? $parameter['bound'] ?? new IdentifierTypeNode('mixed');
                $genericTypeB = $b->genericTypes[$idx] ?? $parameter['bound'] ?? new IdentifierTypeNode('mixed');

                return match ($parameter['variance']) {
                    'in' => self::isSubTypeOf($genericTypeB, $genericTypeA),
                    'out' => self::isSubTypeOf($genericTypeA, $genericTypeB),
                    'inout' => self::isSubTypeOf($genericTypeA, $genericTypeB) && self::isSubTypeOf($genericTypeB, $genericTypeA),
                    default => throw new LogicException("Invalid variance {$parameter['variance']}"),
                };
            });
        }

        $superTypes = isset($typeDef['superTypes']) ? $typeDef['superTypes']($a->genericTypes) : [];
        return Arrays::some($superTypes, static function (TypeNode $superType) use ($b): bool {
            return self::isSubTypeOf($superType, $b);
        });
    }

    /**
     * @return array{
     *     superTypes?: callable(array<TypeNode>): list<TypeNode>,
     *     parameters?: list<array{variance: 'in' | 'out' | 'inout', bound?: TypeNode}>,
     * }
     */
    private static function getGenericTypeDefinition(GenericTypeNode $type): array
    {
        return match ($type->type->name) {
            'array' => [
                'superTypes' => static fn (array $types): array => [
                    new GenericTypeNode(new IdentifierTypeNode('iterable'), [
                        $types[0] ?? new IdentifierTypeNode('mixed'),
                        $types[1] ?? new IdentifierTypeNode('mixed'),
                    ]),
                ],
                'parameters' => [
                    ['variance' => 'out', 'bound' => new UnionTypeNode([new IdentifierTypeNode('int'), new IdentifierTypeNode('string')])],
                    ['variance' => 'out'],
                ],
            ],

            'list' => [
                'superTypes' => static fn (array $types): array => [
                    new GenericTypeNode(new IdentifierTypeNode('array'), [
                        new IdentifierTypeNode('int'),
                        $types[0] ?? new IdentifierTypeNode('mixed'),
                    ]),
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
                'superTypes' => static fn (array $types): array => [
                    new GenericTypeNode(new IdentifierTypeNode(Optional::class), [
                        $types[0] ?? new IdentifierTypeNode('mixed'),
                    ]),
                ],
                'parameters' => [
                    ['variance' => 'out'],
                ],
            ],

            OptionalNone::class => [
                'superTypes' => static fn (): array => [
                    new GenericTypeNode(new IdentifierTypeNode(Optional::class), [new IdentifierTypeNode('never')]),
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
                'noreturn' => new IdentifierTypeNode('never'),
                'number' => new UnionTypeNode([new IdentifierTypeNode('int'), new IdentifierTypeNode('float')]),
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
            if (strtolower($type->type->name) === 'array' && count($type->genericTypes) === 1) {
                return new GenericTypeNode(new IdentifierTypeNode('array'), [
                    new UnionTypeNode([new IdentifierTypeNode('int'), new IdentifierTypeNode('string')]),
                    self::normalizeType($type->genericTypes[0]),
                ]);
            }

            if (strtolower($type->type->name) === 'iterable' && count($type->genericTypes) === 1) {
                return new GenericTypeNode(new IdentifierTypeNode('iterable'), [
                    new IdentifierTypeNode('mixed'),
                    self::normalizeType($type->genericTypes[0]),
                ]);
            }

            if (self::isKeyword($type->type)) {
                return new GenericTypeNode(new IdentifierTypeNode(strtolower($type->type->name)), $type->genericTypes);
            }
        }

        return $type;
    }

}
