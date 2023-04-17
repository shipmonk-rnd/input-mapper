<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Type;

use Nette\Utils\Arrays;
use Nette\Utils\Reflection;
use PhpParser\Node\ComplexType;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeItemNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
use PHPStan\PhpDocParser\Ast\Type\CallableTypeNode;
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
use function array_map;
use function get_object_vars;
use function is_array;
use function is_object;
use function str_contains;
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
            if (!self::isKeyword($type)) {
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

}
