<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Tests\Compiler\Type;

use Countable;
use DateTimeImmutable;
use LogicException;
use PhpParser\Node\ComplexType;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\UnionType;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeItemNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IntersectionTypeNode;
use PHPStan\PhpDocParser\Ast\Type\NullableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionFunction;
use ReflectionParameter;
use ShipMonk\InputMapper\Compiler\Type\PhpDocTypeUtils;
use Traversable;
use function array_map;

class PhpDocTypeUtilsTest extends TestCase
{

    /**
     * @dataProvider provideIsKeywordData
     */
    public function testIsKeyword(string $name, bool $expected): void
    {
        self::assertSame($expected, PhpDocTypeUtils::isKeyword(new IdentifierTypeNode($name)));
    }

    /**
     * @return iterable<array{string, bool}>
     */
    public function provideIsKeywordData(): iterable
    {
        yield ['array', true];
        yield ['Array', true];
        yield ['ARRAY', true];
        yield ['bool', true];
        yield ['callable', true];
        yield ['false', true];
        yield ['float', true];
        yield ['int', true];
        yield ['iterable', true];
        yield ['mixed', true];
        yield ['never', true];
        yield ['null', true];
        yield ['object', true];
        yield ['parent', true];
        yield ['self', true];
        yield ['static', true];
        yield ['string', true];
        yield ['true', true];
        yield ['void', true];
        yield ['boolean', true];
        yield ['Boolean', false];
        yield ['integer', true];
        yield ['double', true];
        yield ['resource', true];
        yield ['unknown', false];
        yield ['positive-int', true];
        yield [DateTimeImmutable::class, false];
    }

    public function testIsMixed(): void
    {
        self::assertTrue(PhpDocTypeUtils::isMixed(new IdentifierTypeNode('mixed')));
        self::assertTrue(PhpDocTypeUtils::isMixed(new IdentifierTypeNode('Mixed')));
        self::assertFalse(PhpDocTypeUtils::isMixed(new IdentifierTypeNode('unknown')));
    }

    public function testIsNull(): void
    {
        self::assertTrue(PhpDocTypeUtils::isNull(new IdentifierTypeNode('null')));
        self::assertTrue(PhpDocTypeUtils::isNull(new IdentifierTypeNode('Null')));
        self::assertFalse(PhpDocTypeUtils::isNull(new IdentifierTypeNode('unknown')));
    }

    public function testFromReflectionType(): void
    {
        $function = static function (
            int $int,
            ?int $intNullable,
            int|float $intOrFloat,
            int|float|null $intOrFloatNullable,
            Traversable & Countable $intersection,
        ): void {
        };

        $parameters = (new ReflectionFunction($function))->getParameters();
        $parameterTypes = array_map(static fn(ReflectionParameter $parameter) => $parameter->getType() ?? throw new LogicException(), $parameters);

        self::assertEquals(
            new IdentifierTypeNode('int'),
            PhpDocTypeUtils::fromReflectionType($parameterTypes[0]),
        );

        self::assertEquals(
            new NullableTypeNode(new IdentifierTypeNode('int')),
            PhpDocTypeUtils::fromReflectionType($parameterTypes[1]),
        );

        self::assertEquals(
            new UnionTypeNode([new IdentifierTypeNode('int'), new IdentifierTypeNode('float')]),
            PhpDocTypeUtils::fromReflectionType($parameterTypes[2]),
        );

        self::assertEquals(
            new UnionTypeNode([
                new IdentifierTypeNode('int'),
                new IdentifierTypeNode('float'),
                new IdentifierTypeNode('null'),
            ]),
            PhpDocTypeUtils::fromReflectionType($parameterTypes[3]),
        );

        self::assertEquals(
            new IntersectionTypeNode([
                new IdentifierTypeNode(Traversable::class),
                new IdentifierTypeNode(Countable::class),
            ]),
            PhpDocTypeUtils::fromReflectionType($parameterTypes[4]),
        );
    }

    /**
     * @dataProvider provideToNativeTypeData
     */
    public function testToNativeType(
        TypeNode $type,
        ComplexType|Identifier|Name $expectedNative,
        bool $expectedIsPhpDocUseful
    ): void
    {
        $nativeType = PhpDocTypeUtils::toNativeType($type, $phpDocUseful);

        self::assertEquals($expectedNative, $nativeType);
        self::assertSame($expectedIsPhpDocUseful, $phpDocUseful);
    }

    /**
     * @return iterable<string, array{TypeNode, ComplexType|Identifier|Name, bool}>
     */
    public function provideToNativeTypeData(): iterable
    {
        yield 'int' => [
            new IdentifierTypeNode('int'),
            new Identifier('int'),
            false,
        ];

        yield 'list' => [
            new IdentifierTypeNode('list'),
            new Identifier('array'),
            true,
        ];

        yield 'positive-int' => [
            new IdentifierTypeNode('positive-int'),
            new Identifier('mixed'),
            true,
        ];

        yield 'DateTimeImmutable' => [
            new IdentifierTypeNode(DateTimeImmutable::class),
            new Name(DateTimeImmutable::class),
            false,
        ];

        yield '?int' => [
            new NullableTypeNode(new IdentifierTypeNode('int')),
            new NullableType(new Identifier('int')),
            false,
        ];

        yield '?(int|float)' => [
            new NullableTypeNode(new UnionTypeNode([new IdentifierTypeNode('int'), new IdentifierTypeNode('float')])),
            new UnionType([new Identifier('int'), new Identifier('float'), new Identifier('null')]),
            false,
        ];

        yield 'int|float' => [
            new UnionTypeNode([new IdentifierTypeNode('int'), new IdentifierTypeNode('float')]),
            new UnionType([new Identifier('int'), new Identifier('float')]),
            false,
        ];

        yield 'int|float|null' => [
            new UnionTypeNode([
                new IdentifierTypeNode('int'),
                new IdentifierTypeNode('float'),
                new IdentifierTypeNode('null'),
            ]),
            new UnionType([new Identifier('int'), new Identifier('float'), new Identifier('null')]),
            false,
        ];

        yield 'int|list' => [
            new UnionTypeNode([new IdentifierTypeNode('int'), new IdentifierTypeNode('list')]),
            new UnionType([new Identifier('int'), new Identifier('array')]),
            true,
        ];

        yield 'array{int, string}' => [
            new ArrayShapeNode([
                new ArrayShapeItemNode(keyName: null, optional: false, valueType: new IdentifierTypeNode('int')),
                new ArrayShapeItemNode(keyName: null, optional: false, valueType: new IdentifierTypeNode('string')),
            ]),
            new Identifier('array'),
            true,
        ];

        yield 'int[]' => [
            new ArrayTypeNode(new IdentifierTypeNode('int')),
            new Identifier('array'),
            true,
        ];

        yield 'array<int>' => [
            new GenericTypeNode(new IdentifierTypeNode('array'), [new IdentifierTypeNode('int')]),
            new Identifier('array'),
            true,
        ];

        yield 'array<int, string>' => [
            new GenericTypeNode(new IdentifierTypeNode('array'), [
                new IdentifierTypeNode('int'),
                new IdentifierTypeNode('string'),
            ]),
            new Identifier('array'),
            true,
        ];

        yield 'iterable<int>' => [
            new GenericTypeNode(new IdentifierTypeNode('iterable'), [new IdentifierTypeNode('int')]),
            new Identifier('iterable'),
            true,
        ];

        yield 'iterable<int, string>' => [
            new GenericTypeNode(new IdentifierTypeNode('iterable'), [
                new IdentifierTypeNode('int'),
                new IdentifierTypeNode('string'),
            ]),
            new Identifier('iterable'),
            true,
        ];
    }

    /**
     * @dataProvider provideIsNullableData
     */
    public function testIsNullable(TypeNode $type, bool $expectedIsNullable): void
    {
        self::assertSame($expectedIsNullable, PhpDocTypeUtils::isNullable($type));
    }

    /**
     * @return iterable<string, array{TypeNode, bool}>
     */
    public function provideIsNullableData(): iterable
    {
        yield 'int' => [
            new IdentifierTypeNode('int'),
            false,
        ];

        yield '?int' => [
            new NullableTypeNode(new IdentifierTypeNode('int')),
            true,
        ];

        yield '?(int|float)' => [
            new NullableTypeNode(new UnionTypeNode([new IdentifierTypeNode('int'), new IdentifierTypeNode('float')])),
            true,
        ];

        yield 'int|float' => [
            new UnionTypeNode([new IdentifierTypeNode('int'), new IdentifierTypeNode('float')]),
            false,
        ];

        yield 'int|float|null' => [
            new UnionTypeNode([
                new IdentifierTypeNode('int'),
                new IdentifierTypeNode('float'),
                new IdentifierTypeNode('null'),
            ]),
            true,
        ];

        yield 'int|list' => [
            new UnionTypeNode([new IdentifierTypeNode('int'), new IdentifierTypeNode('list')]),
            false,
        ];

        yield 'array{int, string}' => [
            new ArrayShapeNode([
                new ArrayShapeItemNode(keyName: null, optional: false, valueType: new IdentifierTypeNode('int')),
                new ArrayShapeItemNode(keyName: null, optional: false, valueType: new IdentifierTypeNode('string')),
            ]),
            false,
        ];

        yield 'int[]' => [
            new ArrayTypeNode(new IdentifierTypeNode('int')),
            false,
        ];

        yield 'array<int>' => [
            new GenericTypeNode(new IdentifierTypeNode('array'), [new IdentifierTypeNode('int')]),
            false,
        ];

        yield 'array<int, string>' => [
            new GenericTypeNode(new IdentifierTypeNode('array'), [
                new IdentifierTypeNode('int'),
                new IdentifierTypeNode('string'),
            ]),
            false,
        ];

        yield 'iterable<int>' => [
            new GenericTypeNode(new IdentifierTypeNode('iterable'), [new IdentifierTypeNode('int')]),
            false,
        ];

        yield 'iterable<int, string>' => [
            new GenericTypeNode(new IdentifierTypeNode('iterable'), [
                new IdentifierTypeNode('int'),
                new IdentifierTypeNode('string'),
            ]),
            false,
        ];
    }

    /**
     * @dataProvider provideMakeNullableData
     */
    public function testMakeNullable(TypeNode $type, TypeNode $expectedType): void
    {
        self::assertEquals($expectedType, PhpDocTypeUtils::makeNullable($type));
    }

    /**
     * @return iterable<string, array{TypeNode, TypeNode}>
     */
    public function provideMakeNullableData(): iterable
    {
        yield 'int' => [
            new IdentifierTypeNode('int'),
            new NullableTypeNode(new IdentifierTypeNode('int')),
        ];

        yield '?int' => [
            new NullableTypeNode(new IdentifierTypeNode('int')),
            new NullableTypeNode(new IdentifierTypeNode('int')),
        ];

        yield '?(int|float)' => [
            new NullableTypeNode(new UnionTypeNode([new IdentifierTypeNode('int'), new IdentifierTypeNode('float')])),
            new NullableTypeNode(new UnionTypeNode([new IdentifierTypeNode('int'), new IdentifierTypeNode('float')])),
        ];

        yield 'int|float' => [
            new UnionTypeNode([new IdentifierTypeNode('int'), new IdentifierTypeNode('float')]),
            new UnionTypeNode([
                new IdentifierTypeNode('int'),
                new IdentifierTypeNode('float'),
                new IdentifierTypeNode('null'),
            ]),
        ];

        yield 'int|float|null' => [
            new UnionTypeNode([
                new IdentifierTypeNode('int'),
                new IdentifierTypeNode('float'),
                new IdentifierTypeNode('null'),
            ]),
            new UnionTypeNode([
                new IdentifierTypeNode('int'),
                new IdentifierTypeNode('float'),
                new IdentifierTypeNode('null'),
            ]),
        ];
    }

    public function testResolve(): void
    {
        $context = new ReflectionClass($this);
        $identifier = new IdentifierTypeNode('TestCase');
        $typeA = $identifier;

        PhpDocTypeUtils::resolve($typeA, $context);
        self::assertSame(TestCase::class, $identifier->name);

        $identifier = new IdentifierTypeNode('TestCase');
        $typeB = new UnionTypeNode([
            $identifier,
            new IdentifierTypeNode('string'),
        ]);

        PhpDocTypeUtils::resolve($typeB, $context);
        self::assertSame(TestCase::class, $identifier->name);
    }

}
