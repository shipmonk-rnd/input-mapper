<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Type;

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
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionFunction;
use ReflectionParameter;
use ShipMonk\InputMapper\Compiler\Type\PhpDocTypeUtils;
use ShipMonkTests\InputMapper\InputMapperTestCase;
use Traversable;
use function array_map;

class PhpDocTypeUtilsTest extends InputMapperTestCase
{

    #[DataProvider('provideIsKeywordData')]
    public function testIsKeyword(string $name, bool $expected): void
    {
        self::assertSame($expected, PhpDocTypeUtils::isKeyword(new IdentifierTypeNode($name)));
    }

    /**
     * @return iterable<array{string, bool}>
     */
    public static function provideIsKeywordData(): iterable
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
        $parameterTypes = array_map(static fn(ReflectionParameter $parameter
        ) => $parameter->getType() ?? throw new LogicException(), $parameters);

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

    #[DataProvider('provideToNativeTypeData')]
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
    public static function provideToNativeTypeData(): iterable
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

    #[DataProvider('provideIsNullableData')]
    public function testIsNullable(TypeNode $type, bool $expectedIsNullable): void
    {
        self::assertSame($expectedIsNullable, PhpDocTypeUtils::isNullable($type));
    }

    /**
     * @return iterable<string, array{TypeNode, bool}>
     */
    public static function provideIsNullableData(): iterable
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

    #[DataProvider('provideMakeNullableData')]
    public function testMakeNullable(TypeNode $type, TypeNode $expectedType): void
    {
        self::assertEquals($expectedType, PhpDocTypeUtils::makeNullable($type));
    }

    /**
     * @return iterable<string, array{TypeNode, TypeNode}>
     */
    public static function provideMakeNullableData(): iterable
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

        $identifier = new IdentifierTypeNode('self');
        $typeC = new UnionTypeNode([
            $identifier,
            new IdentifierTypeNode('string'),
        ]);

        PhpDocTypeUtils::resolve($typeC, $context);
        self::assertSame(self::class, $identifier->name);
    }

    #[DataProvider('provideIsSubTypeOfData')]
    public function testIsSubTypeOf(string $a, string $b, bool $expected): void
    {
        $typeNodeA = $this->parseType($a);
        $typeNodeB = $this->parseType($b);

        self::assertSame($expected, PhpDocTypeUtils::isSubTypeOf($typeNodeA, $typeNodeB));
    }

    private function parseType(string $type): TypeNode
    {
        $lexer = new Lexer();
        $constExprParser = new ConstExprParser(unescapeStrings: true);
        $typeParser = new TypeParser($constExprParser);

        $tokens = new TokenIterator($lexer->tokenize($type));
        $typeNode = $typeParser->parse($tokens);
        $tokens->consumeTokenType(Lexer::TOKEN_END);

        return $typeNode;
    }

    /**
     * @return iterable<string, array{a: string, b: string, expected: bool}>
     */
    public static function provideIsSubTypeOfData(): iterable
    {
        foreach (self::provideIsSubTypeOfDataInner() as $otherType => $setOptions) {
            foreach ($setOptions['true'] as $trueType) {
                yield "{$trueType} is subtype of {$otherType}" => [
                    'a' => $trueType,
                    'b' => $otherType,
                    'expected' => true,
                ];
            }

            foreach ($setOptions['false'] as $falseType) {
                yield "{$falseType} is not subtype of {$otherType}" => [
                    'a' => $falseType,
                    'b' => $otherType,
                    'expected' => false,
                ];
            }
        }
    }

    /**
     * @return iterable<string, array{true: list<string>, false: list<string>}>
     */
    private static function provideIsSubTypeOfDataInner(): iterable
    {
        yield 'array' => [
            'true' => [
                'array',
                'int[]',
                'array{int}',
                'list<int>',
                'array<int>',
                'array<int, int>',
            ],

            'false' => [
                'int',
                'iterable',
            ],
        ];

        yield 'array<int>' => [
            'true' => [
                'array<int>',
                'array<int, int>',
                'list<int>',
                'array{int}',
                'int[]',
            ],

            'false' => [
                'array',
                'array{int, string}',
                'int',
                'iterable',
                'iterable<int>',
            ],
        ];

        yield 'array<mixed>' => [
            'true' => [
                'array<mixed>',
                'array<int>',
                'array',
                'never',
            ],

            'false' => [
                'int',
                'iterable<mixed>',
            ],
        ];

        yield 'array<string, int>' => [
            'true' => [
                'array<string, int>',
                'array{foo: int}',
                'array{"foo": int}',
            ],

            'false' => [
                'array',
                'array<int, string>',
                'array{string}',
                'array{123: string}',
                'array{123: int}',
                'array{"123": string}',
                'array{"123": int}',
                'list<int>',
                'int',
            ],
        ];

        yield 'array<int, string>' => [
            'true' => [
                'list<string>',
                'array{1: string, 4: string}',
                'array{"1": string, "4": string}',
                'array{string, string}',
            ],

            'false' => [
                'array{"foo": string}',
            ],
        ];

        yield 'array{bool}' => [
            'true' => [
                'array{bool}',
                'array{true}',
                'array{false}',
                'array{0: true}',
                'array{"0": true}',
            ],

            'false' => [
                'array',
                'array<bool>',
                'list<bool>',
                'array{string}',
                'array{foo: string}',
                'array{0?: true}',
                'array{1: true}',
                'array{bool, bool}',
                'array{bool, ...}',
            ],
        ];

        yield 'array{bool, int}' => [
            'true' => [
                'array{bool, int}',
                'array{true, 123}',
                'array{0: true, 1: 123}',
            ],

            'false' => [
                'array',
                'array{bool}',
                'array{int}',
                'array{bool, int, ...}',
                'array{0: true, 1: string}',
                'array{0: true, 1?: 123}',
            ],
        ];

        yield 'array{foo: bool, bar?: int}' => [
            'true' => [
                'array{foo: bool, bar?: int}',
                'array{foo: true, bar?: 123}',
                'array{foo: true}',
            ],

            'false' => [
                'array{foo: true, bar: 123}',
                'array{foo: bool, bar: int, baz: string}',
                'array{foo: bool, baz: string}',
                'array{bar?: 123}',
            ],
        ];

        yield 'array{foo: bool, bar?: int, ...}' => [
            'true' => [
                'array{foo: bool, bar?: int, ...}',
                'array{foo: bool, bar?: int}',
                'array{foo: true, bar?: 123}',
                'array{foo: true}',
                'array{foo: bool, bar?: int, baz: string}',
                'array{foo: bool, baz: string}',
            ],

            'false' => [
                'array{foo: true, bar: 123}',
                'array{bar?: 123}',
            ],
        ];

        yield 'bool' => [
            'true' => [
                'bool',
                'boolean',
                'true',
                'false',
                'never',
            ],

            'false' => [
                'int',
            ],
        ];

        yield 'callable' => [
            'true' => [
                'callable',
                'callable(): int',
                'Closure',
                'Closure(): int',
                '"strval"',
            ],

            'false' => [
                'int',
                '"abc"',
                '123',
            ],
        ];

        yield 'double' => [
            'true' => [
                'float',
                'double',
                '1.23',
            ],

            'false' => [
                'string',
                'int',
            ],
        ];

        yield 'false' => [
            'true' => [
                'false',
            ],

            'false' => [
                'true',
            ],
        ];

        yield 'float' => [
            'true' => [
                'float',
                'double',
                '1.23',
            ],

            'false' => [
                'string',
                'int',
            ],
        ];

        yield 'int' => [
            'true' => [
                'int',
                'integer',
                '1',
            ],

            'false' => [
                'string',
                'float',
            ],
        ];

        yield 'iterable' => [
            'true' => [
                'iterable',
                'array',
                'array<int>',
                'array<int, int>',
                'list<int>',
                'Traversable',
                'Iterator',
                'IteratorAggregate',
                'ArrayIterator',
                'ArrayObject',
            ],

            'false' => [
                'int',
                'string',
            ],
        ];

        yield 'list' => [
            'true' => [
                'list',
                'list<int>',
                'array{int, string}',
                'array{0: int, 1: string}',
            ],

            'false' => [
                'int',
                'string',
                'array',
                'array<int>',
                'array{0: int, 2: string}',
            ],
        ];

        yield 'list<int>' => [
            'true' => [
                'list<int>',
                'array{int, int}',
                'array{0: int, 1: int}',
            ],

            'false' => [
                'int',
                'string',
                'array',
                'array<int>',
                'array{int, string}',
                'array{0: int, 2: int}',
            ],
        ];

        yield 'list<bool>' => [
            'true' => [
                'list<bool>',
                'list<true>',
                'list<never>',
            ],

            'false' => [
                'list<bool|null>',
            ],
        ];

        yield 'list<true>' => [
            'true' => [
                'list<true>',
                'list<never>',
            ],

            'false' => [
                'list<false>',
                'list<bool>',
                'list<bool|null>',
            ],
        ];

        yield 'mixed' => [
            'true' => [
                'mixed',
                'int',
                'string',
                'array',
                'array<int>',
            ],

            'false' => [],
        ];

        yield 'never' => [
            'true' => [
                'never',
            ],

            'false' => [
                'mixed',
                'int',
                'string',
                'void',
                'array',
                'array<int>',
            ],
        ];

        yield 'number' => [
            'true' => [
                'int',
                'float',
                '1',
                '1.23',
            ],

            'false' => [
                'string',
            ],
        ];

        yield 'null' => [
            'true' => [
                'null',
            ],

            'false' => [
                'int',
            ],
        ];

        yield 'object' => [
            'true' => [
                'object',
                'stdClass',
                'Iterator<string>',
            ],

            'false' => [
                'int',
                'string',
                'array',
                'array<int>',
            ],
        ];

        yield 'resource' => [
            'true' => [
                'resource',
            ],

            'false' => [
                'int',
            ],
        ];

        yield 'string' => [
            'true' => [
                'string',
                '"abc"',
                'DateTimeImmutable::RFC3339',
            ],

            'false' => [
                'int',
            ],
        ];

        yield 'true' => [
            'true' => [
                'true',
            ],

            'false' => [
                'false',
            ],
        ];

        yield 'scalar' => [
            'true' => [
                'int',
                'string',
                'float',
                'bool',
            ],

            'false' => [
                'array',
                'object',
            ],
        ];

        yield 'void' => [
            'true' => [
                'void',
            ],

            'false' => [
                'int',
            ],
        ];

        yield '?int' => [
            'true' => [
                'null',
                'int',
            ],

            'false' => [
                'string',
            ],
        ];

        yield 'int|string' => [
            'true' => [
                'int',
                'string',
            ],

            'false' => [
                'float',
            ],
        ];

        yield 'Countable & Traversable' => [
            'true' => [
                'Countable & Traversable',
                'Countable',
                'Traversable',
                'Iterator',
                'IteratorAggregate',
                'ArrayIterator',
                'ArrayObject',
            ],

            'false' => [
                'int',
                'string',
                'array',
                'array<int>',
                'stdClass',
                'DateTimeImmutable',
            ],
        ];

        yield 'DateTimeInterface' => [
            'true' => [
                'DateTimeInterface',
                'DateTimeImmutable',
                'DateTime',
            ],

            'false' => [
                'int',
                'string',
                'array',
                'array<int>',
                'stdClass',
            ],
        ];

        yield 'ShipMonk\InputMapper\Runtime\Optional' => [
            'true' => [
                'ShipMonk\InputMapper\Runtime\Optional',
                'ShipMonk\InputMapper\Runtime\Optional<string>',
                'ShipMonk\InputMapper\Runtime\OptionalNone',
                'ShipMonk\InputMapper\Runtime\OptionalSome<string>',
                'never',
            ],

            'false' => [
                'int',
                'string',
                'array',
                'array<int>',
                'stdClass',
            ],
        ];

        yield 'ShipMonk\InputMapper\Runtime\Optional<bool>' => [
            'true' => [
                'ShipMonk\InputMapper\Runtime\Optional<bool>',
                'ShipMonk\InputMapper\Runtime\Optional<true>',
                'ShipMonk\InputMapper\Runtime\Optional<false>',
                'ShipMonk\InputMapper\Runtime\OptionalNone',
                'ShipMonk\InputMapper\Runtime\OptionalSome<bool>',
                'ShipMonk\InputMapper\Runtime\OptionalSome<true>',
                'ShipMonk\InputMapper\Runtime\OptionalSome<false>',
                'never',
            ],

            'false' => [
                'ShipMonk\InputMapper\Runtime\Optional',
                'ShipMonk\InputMapper\Runtime\Optional<string>',
                'ShipMonk\InputMapper\Runtime\OptionalSome<string>',
                'int',
                'string',
                'array',
                'array<int>',
                'stdClass',
            ],
        ];

        yield 'ShipMonk\InputMapper\Runtime\OptionalSome<bool>' => [
            'true' => [
                'ShipMonk\InputMapper\Runtime\OptionalSome<bool>',
                'ShipMonk\InputMapper\Runtime\OptionalSome<true>',
                'ShipMonk\InputMapper\Runtime\OptionalSome<false>',
            ],

            'false' => [
                'ShipMonk\InputMapper\Runtime\Optional',
                'ShipMonk\InputMapper\Runtime\Optional<bool>',
                'ShipMonk\InputMapper\Runtime\OptionalSome<string>',
                'ShipMonk\InputMapper\Runtime\OptionalNone',
                'int',
                'stdClass',
            ],
        ];

        yield 'ShipMonk\InputMapper\Runtime\OptionalNone' => [
            'true' => [
                'ShipMonk\InputMapper\Runtime\OptionalNone',
            ],

            'false' => [
                'ShipMonk\InputMapper\Runtime\Optional',
                'ShipMonk\InputMapper\Runtime\Optional<bool>',
                'ShipMonk\InputMapper\Runtime\OptionalSome<bool>',
                'int',
                'stdClass',
            ],
        ];
    }

}
