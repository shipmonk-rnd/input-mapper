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
use ShipMonk\InputMapper\Compiler\Type\GenericTypeParameter;
use ShipMonk\InputMapper\Compiler\Type\PhpDocTypeUtils;
use ShipMonkTests\InputMapper\InputMapperTestCase;
use Traversable;
use function array_map;
use function array_reverse;

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
        yield ['non-empty-list', true];
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

    /**
     * @param  list<GenericTypeParameter> $genericParameters
     */
    #[DataProvider('provideToNativeTypeData')]
    public function testToNativeType(
        TypeNode $type,
        array $genericParameters,
        ComplexType|Identifier|Name $expectedNative,
        bool $expectedIsPhpDocUseful,
    ): void
    {
        $nativeType = PhpDocTypeUtils::toNativeType($type, $genericParameters, $phpDocUseful);

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
            [],
            new Identifier('int'),
            false,
        ];

        yield 'list' => [
            new IdentifierTypeNode('list'),
            [],
            new Identifier('array'),
            true,
        ];

        yield 'positive-int' => [
            new IdentifierTypeNode('positive-int'),
            [],
            new Identifier('int'),
            true,
        ];

        yield 'negative-int' => [
            new IdentifierTypeNode('negative-int'),
            [],
            new Identifier('int'),
            true,
        ];

        yield 'non-positive-int' => [
            new IdentifierTypeNode('non-positive-int'),
            [],
            new Identifier('int'),
            true,
        ];

        yield 'non-negative-int' => [
            new IdentifierTypeNode('non-negative-int'),
            [],
            new Identifier('int'),
            true,
        ];

        yield 'DateTimeImmutable' => [
            new IdentifierTypeNode(DateTimeImmutable::class),
            [],
            new Name(DateTimeImmutable::class),
            false,
        ];

        yield '?int' => [
            new NullableTypeNode(new IdentifierTypeNode('int')),
            [],
            new NullableType(new Identifier('int')),
            false,
        ];

        yield '?(int|float)' => [
            new NullableTypeNode(new UnionTypeNode([new IdentifierTypeNode('int'), new IdentifierTypeNode('float')])),
            [],
            new UnionType([new Identifier('int'), new Identifier('float'), new Identifier('null')]),
            false,
        ];

        yield 'int|float' => [
            new UnionTypeNode([new IdentifierTypeNode('int'), new IdentifierTypeNode('float')]),
            [],
            new UnionType([new Identifier('int'), new Identifier('float')]),
            false,
        ];

        yield 'int|float|null' => [
            new UnionTypeNode([
                new IdentifierTypeNode('int'),
                new IdentifierTypeNode('float'),
                new IdentifierTypeNode('null'),
            ]),
            [],
            new UnionType([new Identifier('int'), new Identifier('float'), new Identifier('null')]),
            false,
        ];

        yield 'int|list' => [
            new UnionTypeNode([new IdentifierTypeNode('int'), new IdentifierTypeNode('list')]),
            [],
            new UnionType([new Identifier('int'), new Identifier('array')]),
            true,
        ];

        yield 'array{int, string}' => [
            new ArrayShapeNode([
                new ArrayShapeItemNode(keyName: null, optional: false, valueType: new IdentifierTypeNode('int')),
                new ArrayShapeItemNode(keyName: null, optional: false, valueType: new IdentifierTypeNode('string')),
            ]),
            [],
            new Identifier('array'),
            true,
        ];

        yield 'int[]' => [
            new ArrayTypeNode(new IdentifierTypeNode('int')),
            [],
            new Identifier('array'),
            true,
        ];

        yield 'array<int>' => [
            new GenericTypeNode(new IdentifierTypeNode('array'), [new IdentifierTypeNode('int')]),
            [],
            new Identifier('array'),
            true,
        ];

        yield 'array<int, string>' => [
            new GenericTypeNode(new IdentifierTypeNode('array'), [
                new IdentifierTypeNode('int'),
                new IdentifierTypeNode('string'),
            ]),
            [],
            new Identifier('array'),
            true,
        ];

        yield 'iterable<int>' => [
            new GenericTypeNode(new IdentifierTypeNode('iterable'), [new IdentifierTypeNode('int')]),
            [],
            new Identifier('iterable'),
            true,
        ];

        yield 'iterable<int, string>' => [
            new GenericTypeNode(new IdentifierTypeNode('iterable'), [
                new IdentifierTypeNode('int'),
                new IdentifierTypeNode('string'),
            ]),
            [],
            new Identifier('iterable'),
            true,
        ];

        yield 'T' => [
            new IdentifierTypeNode('T'),
            [new GenericTypeParameter('T')],
            new Identifier('mixed'),
            true,
        ];

        yield 'T of iterable' => [
            new IdentifierTypeNode('T'),
            [new GenericTypeParameter('T', bound: new IdentifierTypeNode('iterable'))],
            new Identifier('iterable'),
            true,
        ];

        yield 'list<T>' => [
            new GenericTypeNode(new IdentifierTypeNode('list'), [new IdentifierTypeNode('T')]),
            [new GenericTypeParameter('T')],
            new Identifier('array'),
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

    /**
     * @param  ReflectionClass<object> $context
     * @param  list<string>            $genericParameterNames
     */
    #[DataProvider('provideResolveData')]
    public function testResolve(
        mixed $type,
        ReflectionClass $context,
        array $genericParameterNames,
        string $expectedResolvedType,
    ): void
    {
        PhpDocTypeUtils::resolve($type, $context, $genericParameterNames);
        self::assertSame($expectedResolvedType, (string) $type);
    }

    public static function provideResolveData(): iterable
    {
        yield [
            new IdentifierTypeNode('TestCase'),
            new ReflectionClass(self::class),
            [],
            TestCase::class,
        ];

        yield [
            new UnionTypeNode([
                new IdentifierTypeNode('TestCase'),
                new IdentifierTypeNode('string'),
            ]),
            new ReflectionClass(self::class),
            [],
            '(PHPUnit\\Framework\\TestCase | string)',
        ];

        yield [
            new UnionTypeNode([
                new IdentifierTypeNode('self'),
                new IdentifierTypeNode('string'),
            ]),
            new ReflectionClass(self::class),
            [],
            '(ShipMonkTests\\InputMapper\\Compiler\\Type\\PhpDocTypeUtilsTest | string)',
        ];

        yield [
            new IdentifierTypeNode('T'),
            new ReflectionClass(self::class),
            ['T'],
            'T',
        ];

        yield [
            new UnionTypeNode([
                new IdentifierTypeNode('T'),
                new IdentifierTypeNode('string'),
            ]),
            new ReflectionClass(self::class),
            ['T'],
            '(T | string)',
        ];
    }

    /**
     * @param list<string> $types
     */
    #[DataProvider('provideUnionData')]
    public function testUnion(array $types, string $expected): void
    {
        $typesNodes = [];

        foreach ($types as $type) {
            $typesNodes[] = $this->parseType($type);
        }

        $expectedTypeNode = $this->parseType($expected);
        self::assertEquals($expectedTypeNode, PhpDocTypeUtils::union(...$typesNodes));
    }

    /**
     * @return iterable<string, array{0: list<string>, 1: string}>
     */
    public static function provideUnionData(): iterable
    {
        yield 'empty' => [
            [],
            'never',
        ];

        yield 'int' => [
            ['int'],
            'int',
        ];

        yield 'int | int' => [
            ['int', 'int'],
            'int',
        ];

        yield 'int | number' => [
            ['int', 'number'],
            'number',
        ];

        yield 'number | int' => [
            ['number', 'int'],
            'number',
        ];

        yield 'int | string | never' => [
            ['int', 'string', 'never'],
            'int | string',
        ];

        yield 'int | string | mixed' => [
            ['int', 'string', 'mixed'],
            'mixed',
        ];
    }

    /**
     * @param list<string> $types
     */
    #[DataProvider('provideIntersectData')]
    public function testIntersect(array $types, string $expected, ?string $expectedReversed = null): void
    {
        $typesNodes = [];

        foreach ($types as $type) {
            $typesNodes[] = $this->parseType($type);
        }

        $expectedTypeNode = $this->parseType($expected);
        $expectedTypeNodeReversed = $expectedReversed !== null ? $this->parseType($expectedReversed) : $expectedTypeNode;
        self::assertEquals($expectedTypeNode->__toString(), PhpDocTypeUtils::intersect(...$typesNodes)->__toString());
        self::assertEquals($expectedTypeNodeReversed->__toString(), PhpDocTypeUtils::intersect(...array_reverse($typesNodes))->__toString());
    }

    /**
     * @return iterable<string, array{0: list<string>, 1: string, 2?: string}>
     */
    public static function provideIntersectData(): iterable
    {
        yield 'empty' => [
            [],
            'mixed',
        ];

        yield 'int' => [
            ['int'],
            'int',
        ];

        yield 'int & int' => [
            ['int', 'int'],
            'int',
        ];

        yield 'int & number' => [
            ['int', 'number'],
            'int',
        ];

        yield 'Countable & Traversable & mixed' => [
            ['Countable', 'Traversable', 'mixed'],
            'Countable & Traversable',
            'Traversable & Countable',
        ];

        yield 'Countable & Traversable & never' => [
            ['Countable', 'Traversable', 'never'],
            'never',
        ];

        yield 'array<Countable> & array<Traversable>' => [
            ['array<Countable>', 'array<Traversable>'],
            'array<Countable & Traversable>',
            'array<Traversable & Countable>',
        ];

        yield 'array<Countable> & list<Traversable>' => [
            ['array<Countable>', 'list<Traversable>'],
            'list<Countable & Traversable>',
        ];

        yield 'non-empty-list<mixed> & list<int>' => [
            ['non-empty-list<mixed>', 'list<int>'],
            'non-empty-list<int>',
        ];

        yield 'array<int> & non-empty-list<mixed>' => [
            ['array<int>', 'non-empty-list<mixed>'],
            'non-empty-list<int>',
        ];

        yield 'array<int, int> & non-empty-list<mixed>' => [
            ['array<int, int>', 'non-empty-list<mixed>'],
            'non-empty-list<int>',
        ];

        yield 'array<string, Countable> & iterable<string, Traversable>' => [
            ['array<string, Countable>', 'iterable<string, Traversable>'],
            'array<string, Traversable & Countable>',
        ];

        yield 'array<Countable> & iterable<string, Traversable>' => [
            ['array<Countable>', 'iterable<string, Traversable>'],
            'array<string, Traversable & Countable>',
        ];

        yield 'array & iterable<string, Traversable>' => [
            ['array', 'iterable<string, Traversable>'],
            'array<string, Traversable>',
        ];
    }

    #[DataProvider('provideIsSubTypeOfData')]
    public function testIsSubTypeOf(string $a, string $b, bool $expected): void
    {
        $typeNodeA = $this->parseType($a);
        $typeNodeB = $this->parseType($b);

        self::assertSame($expected, PhpDocTypeUtils::isSubTypeOf($typeNodeA, $typeNodeB));
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
                'non-empty-list',
                'non-empty-list<int>',
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
                'non-empty-list<int>',
                'array{int}',
                'int[]',
            ],

            'false' => [
                'array',
                'array{int, string}',
                'int',
                'iterable',
                'iterable<int>',
                'non-empty-list<string>',
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
                'int<1, 10>',
                'int<min, max>',
                'int<min, 10>',
                'int<1, max>',
                'positive-int',
                'negative-int',
                '1',
            ],

            'false' => [
                'string',
                'float',
            ],
        ];

        yield 'int<3, 5>' => [
            'true' => [
                'int<3, 5>',
                'int<3, 4>',
                'int<4, 5>',
                '3',
                '4',
                '5',
            ],

            'false' => [
                'int',
                'int<1, 2>',
                'int<6, 10>',
                'int<min, 4>',
                'int<4, max>',
                'positive-int',
                'negative-int',
                '2',
                '6',
            ],
        ];

        yield 'int<min, 5>' => [
            'true' => [
                'int<min, 4>',
                'int<min, 5>',
                'int<-7, 5>',
                'int<0, 5>',
                'int<5, 5>',
                'negative-int',
                '-7',
                '0',
                '5',
            ],

            'false' => [
                'int',
                'int<0, 10>',
                'int<0, max>',
                'int<min, 6>',
                'positive-int',
                '6',
            ],
        ];

        yield 'int<3, max>' => [
            'true' => [
                'int<3, 3>',
                'int<3, 4>',
                'int<3, 5>',
                'int<3, max>',
                'int<4, 4>',
                'int<4, 5>',
                'int<4, max>',
                '3',
                '4',
                '5',
                '6',
            ],

            'false' => [
                'int',
                'int<0, 5>',
                'int<2, max>',
                'int<min, max>',
                'positive-int',
                'negative-int',
                '-2',
                '0',
                '2',
            ],
        ];

        yield 'int<min, max>' => [
            'true' => [
                'int',
                'int<min, max>',
                'int<min, 3>',
                'int<3, max>',
                'positive-int',
                'negative-int',
                '-3',
                '0',
                '3',
            ],

            'false' => [
                'string',
                'array',
                'null',
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
                'non-empty-list',
                'non-empty-list<int>',
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

        yield 'negative-int' => [
            'true' => [
                'negative-int',
                'int<min, -1>',
                '-1',
                '-2',
            ],

            'false' => [
                'int',
                'int<0, max>',
                'int<min, max>',
                'int<min, 0>',
                '0',
                '1',
            ],
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

        yield 'non-empty-list' => [
            'true' => [
                'non-empty-list',
                'non-empty-list<int>',
            ],

            'false' => [
                'list',
                'list<int>',
            ],
        ];

        yield 'non-empty-list<int>' => [
            'true' => [
                'non-empty-list<int>',
            ],

            'false' => [
                'list',
                'list<int>',
                'non-empty-list',
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

        yield 'positive-int' => [
            'true' => [
                'positive-int',
                'int<1, max>',
                'int<1, 10>',
                'int<1, 1>',
                '1',
                '10',
            ],

            'false' => [
                'int',
                'int<0, max>',
                'int<min, max>',
                'int<min, 1>',
                '0',
                '-1',
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

    #[DataProvider('provideInferGenericParameterData')]
    public function testInferGenericParameter(
        string $type,
        string $genericTypeName,
        int $parameter,
        string $expectedResult
    ): void
    {
        self::assertEquals(
            $this->parseType($expectedResult),
            PhpDocTypeUtils::inferGenericParameter($this->parseType($type), $genericTypeName, $parameter),
        );
    }

    /**
     * @return iterable<array{0: string, 1: string, 2: int, 3: string}>
     */
    public static function provideInferGenericParameterData(): iterable
    {
        yield [
            'list<int>',
            'list',
            0,
            'int',
        ];

        yield [
            'list<int>|list<string>',
            'list',
            0,
            'int|string',
        ];

        yield [
            'array<int>',
            'array',
            0,
            'int|string',
        ];

        yield [
            'array<int>',
            'array',
            1,
            'int',
        ];

        yield [
            'list<string>',
            'array',
            0,
            'int',
        ];

        yield [
            'list<string>',
            'array',
            1,
            'string',
        ];

        yield [
            'ShipMonk\InputMapper\Runtime\Optional<Countable> & ShipMonk\InputMapper\Runtime\Optional<Traversable>',
            'ShipMonk\InputMapper\Runtime\Optional',
            0,
            'Countable & Traversable',
        ];
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

}
