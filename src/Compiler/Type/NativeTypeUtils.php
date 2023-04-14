<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Type;

use LogicException;
use PhpParser\Node\ComplexType;
use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\UnionType;
use function count;
use function get_debug_type;
use function sprintf;

class NativeTypeUtils
{

    public static function createNullable(ComplexType|Identifier|Name $type): ComplexType|Identifier|Name
    {
        if ($type instanceof Identifier) {
            $isNullable = $type->toLowerString() === 'null' || $type->toLowerString() === 'mixed';
            return $isNullable ? $type : new NullableType($type);
        }

        if ($type instanceof Name) {
            return new NullableType($type);
        }

        if ($type instanceof NullableType) {
            return $type;
        }

        return self::createUnion($type, new Identifier('null'));
    }

    public static function createUnion(ComplexType|Identifier|Name ...$members): ComplexType|Identifier|Name
    {
        $types = [];

        foreach ($members as $member) {
            if ($member instanceof Identifier || $member instanceof Name || $member instanceof IntersectionType) {
                $types[] = $member;
                continue;
            }

            if ($member instanceof UnionType) {
                $types = [...$types, ...$member->types];
                continue;
            }

            if ($member instanceof NullableType) {
                $types[] = $member->type;
                $types[] = new Identifier('null');
                continue;
            }

            throw new LogicException(sprintf('Unexpected union member type: %s', get_debug_type($member)));
        }

        return match (count($types)) {
            0 => new Identifier('never'),
            1 => $types[0],
            default => new UnionType($types),
        };
    }

    public static function createIntersection(ComplexType|Identifier|Name ...$members): ComplexType|Identifier|Name
    {
        $types = [];

        foreach ($members as $member) {
            if ($member instanceof Identifier || $member instanceof Name) {
                $types[] = $member;
                continue;
            }

            if ($member instanceof IntersectionType) {
                $types = [...$types, ...$member->types];
                continue;
            }

            throw new LogicException(sprintf('Unexpected intersection member type: %s', get_debug_type($member)));
        }

        return match (count($types)) {
            0 => new Identifier('mixed'),
            1 => $types[0],
            default => new IntersectionType($types),
        };
    }

}
