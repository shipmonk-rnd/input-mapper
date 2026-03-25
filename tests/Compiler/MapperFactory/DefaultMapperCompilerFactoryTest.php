<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\MapperFactory;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\PhpDocParser\ParserConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use ShipMonk\InputMapper\Compiler\Attribute\MapInt;
use ShipMonk\InputMapper\Compiler\Attribute\MapperCompilerProvider;
use ShipMonk\InputMapper\Compiler\Exception\CannotCreateMapperCompilerException;
use ShipMonk\InputMapper\Compiler\Mapper\Input\ArrayInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Input\ArrayShapeInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Input\BoolInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Input\DateTimeImmutableInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Input\DefaultValueInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Input\DelegateInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Input\DiscriminatedObjectInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Input\EnumInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Input\FloatInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Input\IntInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Input\ListInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Input\MixedInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Input\NullableInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Input\ObjectInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Input\OptionalInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Input\StringInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Input\ValidatedInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Output\ArrayOutputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Output\ArrayShapeOutputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Output\DelegateOutputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Output\DiscriminatedObjectOutputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Output\ListOutputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Output\ObjectOutputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\PassthroughMapperCompiler;
use ShipMonk\InputMapper\Compiler\MapperFactory\DefaultMapperCompilerFactory;
use ShipMonk\InputMapper\Compiler\Type\GenericTypeParameter;
use ShipMonk\InputMapper\Compiler\Validator\Array\AssertListLength;
use ShipMonk\InputMapper\Compiler\Validator\Int\AssertInt32;
use ShipMonk\InputMapper\Compiler\Validator\Int\AssertIntRange;
use ShipMonk\InputMapper\Compiler\Validator\Int\AssertNegativeInt;
use ShipMonk\InputMapper\Compiler\Validator\Int\AssertNonNegativeInt;
use ShipMonk\InputMapper\Compiler\Validator\Int\AssertNonPositiveInt;
use ShipMonk\InputMapper\Compiler\Validator\Int\AssertPositiveInt;
use ShipMonk\InputMapper\Compiler\Validator\String\AssertStringLength;
use ShipMonk\InputMapper\Compiler\Validator\String\AssertStringNonEmpty;
use ShipMonk\InputMapper\Compiler\Validator\String\AssertUrl;
use ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data\HierarchicalChildOneInput;
use ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data\HierarchicalChildTwoInput;
use ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data\HierarchicalParentInput;
use ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data\SimplePersonInput;
use ShipMonk\InputMapperTests\Compiler\MapperFactory\Data\AnimalCatInput;
use ShipMonk\InputMapperTests\Compiler\MapperFactory\Data\AnimalDogInput;
use ShipMonk\InputMapperTests\Compiler\MapperFactory\Data\AnimalInput;
use ShipMonk\InputMapperTests\Compiler\MapperFactory\Data\AnimalType;
use ShipMonk\InputMapperTests\Compiler\MapperFactory\Data\BrandInput;
use ShipMonk\InputMapperTests\Compiler\MapperFactory\Data\BrandInputWithDefaultValues;
use ShipMonk\InputMapperTests\Compiler\MapperFactory\Data\CarFilterInput;
use ShipMonk\InputMapperTests\Compiler\MapperFactory\Data\CarInput;
use ShipMonk\InputMapperTests\Compiler\MapperFactory\Data\CarInputWithVarTags;
use ShipMonk\InputMapperTests\Compiler\MapperFactory\Data\ColorEnum;
use ShipMonk\InputMapperTests\Compiler\MapperFactory\Data\EnumFilterInput;
use ShipMonk\InputMapperTests\Compiler\MapperFactory\Data\EqualsFilterInput;
use ShipMonk\InputMapperTests\Compiler\MapperFactory\Data\InFilterInput;
use ShipMonk\InputMapperTests\Compiler\MapperFactory\Data\InputWithDate;
use ShipMonk\InputMapperTests\Compiler\MapperFactory\Data\InputWithIncompatibleMapperCompiler;
use ShipMonk\InputMapperTests\Compiler\MapperFactory\Data\InputWithoutConstructor;
use ShipMonk\InputMapperTests\Compiler\MapperFactory\Data\InputWithPrivateConstructor;
use ShipMonk\InputMapperTests\Compiler\MapperFactory\Data\InputWithRenamedSourceKey;
use ShipMonk\InputMapperTests\InputMapperTestCase;

class DefaultMapperCompilerFactoryTest extends InputMapperTestCase
{

    /**
     * @param array<string, mixed> $options
     */
    #[DataProvider('provideCreateInputOkData')]
    public function testCreateInputOk(
        string $type,
        array $options,
        MapperCompiler $expectedMapperCompiler,
    ): void
    {
        $factory = self::createFactory();
        $phpDocType = self::parseType($type);

        $mapperCompiler = $factory->create($phpDocType, $options)->getInputMapperCompiler();

        self::assertEquals($expectedMapperCompiler, $mapperCompiler);
    }

    /**
     * @return iterable<array{string, array<string, mixed>, MapperCompiler}>
     */
    public static function provideCreateInputOkData(): iterable
    {
        yield 'CarInput' => [
            CarInput::class,
            [],
            new ObjectInputMapperCompiler(CarInput::class, [
                'id' => new IntInputMapperCompiler(),
                'name' => new ValidatedInputMapperCompiler(new StringInputMapperCompiler(), [new AssertStringLength(exact: 7)]),
                'brand' => new OptionalInputMapperCompiler(new DelegateInputMapperCompiler(BrandInput::class)),
                'numbers' => new ListInputMapperCompiler(new ValidatedInputMapperCompiler(new IntInputMapperCompiler(), [new AssertPositiveInt()])),
                'url' => new NullableInputMapperCompiler(new ValidatedInputMapperCompiler(new StringInputMapperCompiler(), [new AssertUrl()])),
            ]),
        ];

        yield 'CarInputWithVarTags' => [
            CarInputWithVarTags::class,
            [],
            new ObjectInputMapperCompiler(CarInputWithVarTags::class, [
                'id' => new IntInputMapperCompiler(),
                'name' => new ValidatedInputMapperCompiler(new StringInputMapperCompiler(), [new AssertStringLength(exact: 7)]),
                'brand' => new OptionalInputMapperCompiler(new DelegateInputMapperCompiler(BrandInput::class)),
                'numbers' => new ListInputMapperCompiler(new ValidatedInputMapperCompiler(new IntInputMapperCompiler(), [new AssertPositiveInt()])),
                'url' => new NullableInputMapperCompiler(new ValidatedInputMapperCompiler(new StringInputMapperCompiler(), [new AssertUrl()])),
            ]),
        ];

        yield 'CarInput with forced root delegation' => [
            CarInput::class,
            [DefaultMapperCompilerFactory::DELEGATE_OBJECT_MAPPING => true],
            new DelegateInputMapperCompiler(CarInput::class),
        ];

        yield 'BrandInput (with allowed extra keys)' => [
            BrandInput::class,
            [],
            new ObjectInputMapperCompiler(
                BrandInput::class,
                [
                    'name' => new StringInputMapperCompiler(),
                    'foundedIn' => new ValidatedInputMapperCompiler(
                        new IntInputMapperCompiler(),
                        [new AssertIntRange(gte: 1_900, lte: 2_100)],
                    ),
                    'founders' => new ValidatedInputMapperCompiler(
                        new ListInputMapperCompiler(new StringInputMapperCompiler()),
                        [new AssertListLength(min: 1)],
                    ),
                ],
                allowExtraKeys: true,
            ),
        ];

        yield 'BrandInputWithDefaultValues' => [
            BrandInputWithDefaultValues::class,
            [],
            new ObjectInputMapperCompiler(
                BrandInputWithDefaultValues::class,
                [
                    'name' => new DefaultValueInputMapperCompiler(
                        new ValidatedInputMapperCompiler(
                            new StringInputMapperCompiler(),
                            [new AssertStringLength(min: 5)],
                        ),
                        'ShipMonk',
                    ),
                    'foundedIn' => new DefaultValueInputMapperCompiler(
                        new NullableInputMapperCompiler(new ValidatedInputMapperCompiler(
                            new IntInputMapperCompiler(),
                            [new AssertInt32()],
                        )),
                        null,
                    ),
                    'founders' => new DefaultValueInputMapperCompiler(
                        new ListInputMapperCompiler(new StringInputMapperCompiler()),
                        ['Jan Bednář'],
                    ),
                ],
            ),
        ];

        yield 'CarFilterInput' => [
            CarFilterInput::class,
            [],
            new ObjectInputMapperCompiler(
                className: CarFilterInput::class,
                constructorArgsMapperCompilers: [
                    'id' => new DelegateInputMapperCompiler(InFilterInput::class, [
                        new IntInputMapperCompiler(),
                    ]),
                    'color' => new DelegateInputMapperCompiler(EqualsFilterInput::class, [
                        new DelegateInputMapperCompiler(ColorEnum::class),
                    ]),
                ],
            ),
        ];

        yield 'AnimalInput' => [
            AnimalInput::class,
            [],
            new DiscriminatedObjectInputMapperCompiler(
                className: AnimalInput::class,
                discriminatorKeyName: 'type',
                subtypeCompilers: [
                    AnimalType::Cat->value => new DelegateInputMapperCompiler(AnimalCatInput::class),
                    AnimalType::Dog->value => new DelegateInputMapperCompiler(AnimalDogInput::class),
                ],
            ),
        ];

        yield 'ColorEnum' => [
            ColorEnum::class,
            [],
            new EnumInputMapperCompiler(ColorEnum::class, new StringInputMapperCompiler()),
        ];

        yield 'DateTimeImmutable' => [
            DateTimeImmutable::class,
            [],
            new DateTimeImmutableInputMapperCompiler(),
        ];

        yield 'DateTimeInterface' => [
            DateTimeInterface::class,
            [],
            new DateTimeImmutableInputMapperCompiler(),
        ];

        yield 'EqualsFilterInput' => [
            EqualsFilterInput::class,
            [],
            new ObjectInputMapperCompiler(
                className: EqualsFilterInput::class,
                constructorArgsMapperCompilers: [
                    'equals' => new DelegateInputMapperCompiler('T'),
                ],
                genericParameters: [
                    new GenericTypeParameter('T'),
                ],
            ),
        ];

        yield 'InputWithDate' => [
            InputWithDate::class,
            [],
            new ObjectInputMapperCompiler(InputWithDate::class, [
                'date' => new DateTimeImmutableInputMapperCompiler('Y-m-d', 'date string in Y-m-d format'),
                'dateTime' => new DelegateInputMapperCompiler(DateTimeImmutable::class),
            ]),
        ];

        yield 'InputWithRenamedSourceKey' => [
            InputWithRenamedSourceKey::class,
            [],
            new ObjectInputMapperCompiler(
                className: InputWithRenamedSourceKey::class,
                constructorArgsMapperCompilers: [
                    'old_value' => new IntInputMapperCompiler(),
                    'new_value' => new IntInputMapperCompiler(),
                ],
            ),
        ];

        yield 'array' => [
            'array',
            [],
            new ArrayInputMapperCompiler(new MixedInputMapperCompiler(), new MixedInputMapperCompiler()),
        ];

        yield 'bool' => [
            'bool',
            [],
            new BoolInputMapperCompiler(),
        ];

        yield 'int' => [
            'int',
            [],
            new IntInputMapperCompiler(),
        ];

        yield 'float' => [
            'float',
            [],
            new FloatInputMapperCompiler(),
        ];

        yield 'mixed' => [
            'mixed',
            [],
            new MixedInputMapperCompiler(),
        ];

        yield 'string' => [
            'string',
            [],
            new StringInputMapperCompiler(),
        ];

        yield '?int' => [
            '?int',
            [],
            new NullableInputMapperCompiler(new IntInputMapperCompiler()),
        ];

        yield 'int|null' => [
            'int|null',
            [],
            new NullableInputMapperCompiler(new IntInputMapperCompiler()),
        ];

        yield 'int[]' => [
            'int[]',
            [],
            new ArrayInputMapperCompiler(new MixedInputMapperCompiler(), new IntInputMapperCompiler()),
        ];

        yield 'array<int>' => [
            'array<int>',
            [],
            new ArrayInputMapperCompiler(new MixedInputMapperCompiler(), new IntInputMapperCompiler()),
        ];

        yield 'array<string, int>' => [
            'array<string, int>',
            [],
            new ArrayInputMapperCompiler(new StringInputMapperCompiler(), new IntInputMapperCompiler()),
        ];

        yield 'list' => [
            'list',
            [],
            new ListInputMapperCompiler(new MixedInputMapperCompiler()),
        ];

        yield 'list<int>' => [
            'list<int>',
            [],
            new ListInputMapperCompiler(new IntInputMapperCompiler()),
        ];

        yield 'int<0, 10>' => [
            'int<0, 10>',
            [],
            new ValidatedInputMapperCompiler(new IntInputMapperCompiler(), [
                new AssertIntRange(gte: 0, lte: 10),
            ]),
        ];

        yield 'int<min, 10>' => [
            'int<min, 10>',
            [],
            new ValidatedInputMapperCompiler(new IntInputMapperCompiler(), [
                new AssertIntRange(lte: 10),
            ]),
        ];

        yield 'int<0, max>' => [
            'int<0, max>',
            [],
            new ValidatedInputMapperCompiler(new IntInputMapperCompiler(), [
                new AssertIntRange(gte: 0),
            ]),
        ];

        yield 'positive-int' => [
            'positive-int',
            [],
            new ValidatedInputMapperCompiler(new IntInputMapperCompiler(), [
                new AssertPositiveInt(),
            ]),
        ];

        yield 'negative-int' => [
            'negative-int',
            [],
            new ValidatedInputMapperCompiler(new IntInputMapperCompiler(), [
                new AssertNegativeInt(),
            ]),
        ];

        yield 'non-empty-list' => [
            'non-empty-list',
            [],
            new ValidatedInputMapperCompiler(new ListInputMapperCompiler(new MixedInputMapperCompiler()), [
                new AssertListLength(min: 1),
            ]),
        ];

        yield 'non-empty-list<int>' => [
            'non-empty-list<int>',
            [],
            new ValidatedInputMapperCompiler(new ListInputMapperCompiler(new IntInputMapperCompiler()), [
                new AssertListLength(min: 1),
            ]),
        ];

        yield 'non-empty-string' => [
            'non-empty-string',
            [],
            new ValidatedMapperCompiler(new MapString(), [
                new AssertStringNonEmpty(),
            ]),
        ];

        yield 'non-positive-int' => [
            'non-positive-int',
            [],
            new ValidatedInputMapperCompiler(new IntInputMapperCompiler(), [
                new AssertNonPositiveInt(),
            ]),
        ];

        yield 'non-negative-int' => [
            'non-negative-int',
            [],
            new ValidatedInputMapperCompiler(new IntInputMapperCompiler(), [
                new AssertNonNegativeInt(),
            ]),
        ];

        yield '?list<string>' => [
            '?list<string>',
            [],
            new NullableInputMapperCompiler(new ListInputMapperCompiler(new StringInputMapperCompiler())),
        ];

        yield 'ShipMonk\InputMapper\Runtime\Optional<string>' => [
            'ShipMonk\InputMapper\Runtime\Optional<string>',
            [],
            new OptionalInputMapperCompiler(new StringInputMapperCompiler()),
        ];

        yield 'array sealed shape' => [
            'array{foo: string, bar: int}',
            [],
            new ArrayShapeInputMapperCompiler(
                items: [
                    ['key' => 'foo', 'mapper' => new StringInputMapperCompiler(), 'optional' => false],
                    ['key' => 'bar', 'mapper' => new IntInputMapperCompiler(), 'optional' => false],
                ],
                sealed: true,
            ),
        ];

        yield 'array unsealed shape' => [
            'array{foo: string, bar: int, ...}',
            [],
            new ArrayShapeInputMapperCompiler(
                items: [
                    ['key' => 'foo', 'mapper' => new StringInputMapperCompiler(), 'optional' => false],
                    ['key' => 'bar', 'mapper' => new IntInputMapperCompiler(), 'optional' => false],
                ],
                sealed: false,
            ),
        ];
    }

    /**
     * @param array<string, mixed> $options
     */
    #[DataProvider('provideCreateOutputOkData')]
    public function testCreateOutputOk(
        string $type,
        array $options,
        MapperCompiler $expectedMapperCompiler,
    ): void
    {
        $factory = self::createFactory();
        $phpDocType = self::parseType($type);

        $mapperCompiler = $factory->create($phpDocType, $options)->getOutputMapperCompiler();

        self::assertEquals($expectedMapperCompiler, $mapperCompiler);
    }

    /**
     * @return iterable<array{string, array<string, mixed>, MapperCompiler}>
     */
    public static function provideCreateOutputOkData(): iterable
    {
        yield 'int' => [
            'int',
            [],
            new PassthroughMapperCompiler(new IdentifierTypeNode('int')),
        ];

        yield 'string' => [
            'string',
            [],
            new PassthroughMapperCompiler(new IdentifierTypeNode('string')),
        ];

        yield 'bool' => [
            'bool',
            [],
            new PassthroughMapperCompiler(new IdentifierTypeNode('bool')),
        ];

        yield 'float' => [
            'float',
            [],
            new PassthroughMapperCompiler(new IdentifierTypeNode('float')),
        ];

        yield 'mixed' => [
            'mixed',
            [],
            new PassthroughMapperCompiler(new IdentifierTypeNode('mixed')),
        ];

        yield 'SimplePersonInput' => [
            SimplePersonInput::class,
            [],
            new ObjectOutputMapperCompiler(SimplePersonInput::class, [
                'id' => ['id', new PassthroughMapperCompiler(new IdentifierTypeNode('int'))],
                'name' => ['name', new PassthroughMapperCompiler(new IdentifierTypeNode('string'))],
            ]),
        ];

        yield 'InputWithRenamedSourceKey' => [
            InputWithRenamedSourceKey::class,
            [],
            new ObjectOutputMapperCompiler(InputWithRenamedSourceKey::class, [
                'oldValue' => ['old_value', new PassthroughMapperCompiler(new IdentifierTypeNode('int'))],
                'newValue' => ['new_value', new PassthroughMapperCompiler(new IdentifierTypeNode('int'))],
            ]),
        ];

        yield 'list<int>' => [
            'list<int>',
            [],
            new ListOutputMapperCompiler(new PassthroughMapperCompiler(new IdentifierTypeNode('int'))),
        ];

        yield 'array<string, int>' => [
            'array<string, int>',
            [],
            new ArrayOutputMapperCompiler(
                new PassthroughMapperCompiler(new IdentifierTypeNode('string')),
                new PassthroughMapperCompiler(new IdentifierTypeNode('int')),
            ),
        ];

        yield 'array<int>' => [
            'array<int>',
            [],
            new ArrayOutputMapperCompiler(
                new PassthroughMapperCompiler(new IdentifierTypeNode('mixed')),
                new PassthroughMapperCompiler(new IdentifierTypeNode('int')),
            ),
        ];

        yield 'int[]' => [
            'int[]',
            [],
            new ArrayOutputMapperCompiler(
                new PassthroughMapperCompiler(new IdentifierTypeNode('mixed')),
                new PassthroughMapperCompiler(new IdentifierTypeNode('int')),
            ),
        ];

        yield 'array{a: int, b?: string}' => [
            'array{a: int, b?: string}',
            [],
            new ArrayShapeOutputMapperCompiler([
                ['key' => 'a', 'mapper' => new PassthroughMapperCompiler(new IdentifierTypeNode('int')), 'optional' => false],
                ['key' => 'b', 'mapper' => new PassthroughMapperCompiler(new IdentifierTypeNode('string')), 'optional' => true],
            ], sealed: true),
        ];

        yield 'HierarchicalParentInput (discriminated)' => [
            HierarchicalParentInput::class,
            [],
            new DiscriminatedObjectOutputMapperCompiler(
                HierarchicalParentInput::class,
                [
                    'childOne' => new DelegateOutputMapperCompiler(HierarchicalChildOneInput::class),
                    'childTwo' => new DelegateOutputMapperCompiler(HierarchicalChildTwoInput::class),
                ],
            ),
        ];
    }

    /**
     * @param array<string, mixed> $options
     */
    #[DataProvider('provideCreateErrorData')]
    public function testCreateError(
        string $type,
        array $options,
        ?string $expectedMessage = null,
    ): void
    {
        $factory = self::createFactory();
        $phpDocType = self::parseType($type);

        self::assertException(
            CannotCreateMapperCompilerException::class,
            $expectedMessage,
            static fn () => $factory->create($phpDocType, $options),
        );
    }

    /**
     * @return iterable<array{0: string, 1: array<string, mixed>, 2?: string}>
     */
    public static function provideCreateErrorData(): iterable
    {
        yield 'NonExistingClass' => [
            'NonExistingClass',
            [],
            'Cannot create mapper for type NonExistingClass, because there is no class, interface or enum with this name',
        ];

        yield 'InputWithoutConstructor' => [
            InputWithoutConstructor::class,
            [],
            'Cannot create mapper for type ShipMonk\InputMapperTests\Compiler\MapperFactory\Data\InputWithoutConstructor, because class has no constructor',
        ];

        yield 'InputWithPrivateConstructor' => [
            InputWithPrivateConstructor::class,
            [],
            'Cannot create mapper for type ShipMonk\InputMapperTests\Compiler\MapperFactory\Data\InputWithPrivateConstructor, because class has a non-public constructor',
        ];

        yield 'InputWithIncompatibleMapperCompiler' => [
            InputWithIncompatibleMapperCompiler::class,
            [],
            'Cannot use mapper ShipMonk\InputMapper\Compiler\Mapper\Input\StringInputMapperCompiler for parameter $id of method ShipMonk\InputMapperTests\Compiler\MapperFactory\Data\InputWithIncompatibleMapperCompiler::__construct, because mapper output type \'string\' is not compatible with parameter type \'int\'',
        ];

        yield 'DateTime' => [
            DateTime::class,
            [DefaultMapperCompilerFactory::DELEGATE_OBJECT_MAPPING => false],
        ];

        yield 'array<int, int, int>' => [
            'array<int, int, int>',
            [],
        ];

        yield 'callable(): void' => [
            'callable(): void',
            [],
            'Cannot create mapper for type callable(): void',
        ];

        yield 'int<foo, bar>' => [
            'int<foo, bar>',
            [],
            'Cannot create mapper for type int<foo, bar>, because integer boundary foo is not supported',
        ];

        yield 'EnumFilterInput<int>' => [
            EnumFilterInput::class . '<int>',
            [],
            'Cannot create mapper for type ShipMonk\\InputMapperTests\\Compiler\\MapperFactory\\Data\\EnumFilterInput<int>, because type int is not a subtype of BackedEnum',
        ];
    }

    public function testCreateWithCustomFactory(): void
    {
        $factory = self::createFactory();

        $customProvider = new MapInt();

        $factory->setMapperCompilerFactory(CarInput::class, static function (string $className, array $options) use ($customProvider): MapperCompilerProvider {
            self::assertSame(CarInput::class, $className);
            self::assertSame([], $options);

            return $customProvider;
        });

        $phpDocType = new IdentifierTypeNode(CarInput::class);
        self::assertSame($customProvider, $factory->create($phpDocType));
    }

    private static function createFactory(): DefaultMapperCompilerFactory
    {
        $config = new ParserConfig([]);
        $phpDocLexer = new Lexer($config);
        $phpDocConstExprParser = new ConstExprParser($config);
        $phpDocTypeParser = new TypeParser($config, $phpDocConstExprParser);
        $phpDocParser = new PhpDocParser($config, $phpDocTypeParser, $phpDocConstExprParser);

        return new DefaultMapperCompilerFactory($phpDocLexer, $phpDocParser);
    }

    private static function parseType(string $type): TypeNode
    {
        $config = new ParserConfig([]);
        $phpDocLexer = new Lexer($config);
        $phpDocConstExprParser = new ConstExprParser($config);
        $phpDocTypeParser = new TypeParser($config, $phpDocConstExprParser);

        return $phpDocTypeParser->parse(new TokenIterator($phpDocLexer->tokenize($type)));
    }

}
