<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Type;

use ArrayObject;
use Countable;
use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PHPUnit\Framework\Attributes\DataProvider;
use ShipMonk\InputMapper\Compiler\Type\NativeTypeUtils;
use ShipMonk\InputMapperTests\InputMapperTestCase;
use Traversable;

class NativeTypeUtilsTest extends InputMapperTestCase
{

    #[DataProvider('provideIsSubTypeOfData')]
    public function testIsSubTypeOf(
        Identifier|Name $a,
        Identifier|Name $b,
        bool $expected,
    ): void
    {
        self::assertSame($expected, NativeTypeUtils::isSubTypeOf($a, $b));
    }

    /**
     * @return iterable<string, array{Identifier|Name, Identifier|Name, bool}>
     */
    public static function provideIsSubTypeOfData(): iterable
    {
        yield 'same identifiers' => [
            new Identifier('int'),
            new Identifier('int'),
            true,
        ];

        yield 'case insensitive identifiers' => [
            new Identifier('Int'),
            new Identifier('int'),
            true,
        ];

        yield 'different identifiers' => [
            new Identifier('int'),
            new Identifier('string'),
            false,
        ];

        yield 'same names' => [
            new Name(Countable::class),
            new Name(Countable::class),
            true,
        ];

        yield 'name is subtype via inheritance' => [
            new Name(ArrayObject::class),
            new Name(Countable::class),
            true,
        ];

        yield 'name is not subtype' => [
            new Name(Countable::class),
            new Name(Traversable::class),
            false,
        ];

        yield 'identifier vs name' => [
            new Identifier('int'),
            new Name(Countable::class),
            false,
        ];

        yield 'name vs identifier' => [
            new Name(Countable::class),
            new Identifier('int'),
            false,
        ];
    }

    /**
     * @param list<Identifier|Name|IntersectionType> $members
     */
    #[DataProvider('provideCreateIntersectionData')]
    public function testCreateIntersection(
        array $members,
        Identifier|Name|IntersectionType $expected,
    ): void
    {
        self::assertEquals($expected, NativeTypeUtils::createIntersection(...$members));
    }

    /**
     * @return iterable<string, array{list<Identifier|Name|IntersectionType>, Identifier|Name|IntersectionType}>
     */
    public static function provideCreateIntersectionData(): iterable
    {
        yield 'empty returns mixed' => [
            [],
            new Identifier('mixed'),
        ];

        yield 'single identifier' => [
            [new Identifier('int')],
            new Identifier('int'),
        ];

        yield 'single name' => [
            [new Name(Countable::class)],
            new Name(Countable::class),
        ];

        yield 'two different names' => [
            [new Name(Countable::class), new Name(Traversable::class)],
            new IntersectionType([new Name(Countable::class), new Name(Traversable::class)]),
        ];

        yield 'duplicate identifiers are deduplicated' => [
            [new Identifier('int'), new Identifier('int')],
            new Identifier('int'),
        ];

        yield 'flattens nested intersection' => [
            [new IntersectionType([new Name(Countable::class), new Name(Traversable::class)])],
            new IntersectionType([new Name(Countable::class), new Name(Traversable::class)]),
        ];

        yield 'subtype eliminated: ArrayObject is subtype of Countable' => [
            [new Name(ArrayObject::class), new Name(Countable::class)],
            new Name(ArrayObject::class),
        ];

        yield 'subtype eliminated reversed: Countable and ArrayObject' => [
            [new Name(Countable::class), new Name(ArrayObject::class)],
            new Name(ArrayObject::class),
        ];
    }

}
