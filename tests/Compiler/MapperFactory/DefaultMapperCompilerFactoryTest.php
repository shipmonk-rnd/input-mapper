<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\MapperFactory;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPUnit\Framework\Attributes\DataProvider;
use ShipMonk\InputMapper\Compiler\Exception\CannotCreateMapperCompilerException;
use ShipMonk\InputMapper\Compiler\Mapper\Array\ArrayShapeItemMapping;
use ShipMonk\InputMapper\Compiler\Mapper\Array\MapArray;
use ShipMonk\InputMapper\Compiler\Mapper\Array\MapArrayShape;
use ShipMonk\InputMapper\Compiler\Mapper\Array\MapList;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Mixed\MapMixed;
use ShipMonk\InputMapper\Compiler\Mapper\Object\DelegateMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Object\MapDateTimeImmutable;
use ShipMonk\InputMapper\Compiler\Mapper\Object\MapEnum;
use ShipMonk\InputMapper\Compiler\Mapper\Object\MapObject;
use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapBool;
use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapFloat;
use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapInt;
use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapString;
use ShipMonk\InputMapper\Compiler\Mapper\Wrapper\MapNullable;
use ShipMonk\InputMapper\Compiler\Mapper\Wrapper\MapOptional;
use ShipMonk\InputMapper\Compiler\Mapper\Wrapper\ValidatedMapperCompiler;
use ShipMonk\InputMapper\Compiler\MapperFactory\DefaultMapperCompilerFactory;
use ShipMonk\InputMapper\Compiler\Validator\Int\AssertIntRange;
use ShipMonk\InputMapper\Compiler\Validator\Int\AssertPositiveInt;
use ShipMonk\InputMapper\Compiler\Validator\String\AssertStringLength;
use ShipMonkTests\InputMapper\Compiler\MapperFactory\Data\BrandInput;
use ShipMonkTests\InputMapper\Compiler\MapperFactory\Data\CarInput;
use ShipMonkTests\InputMapper\Compiler\MapperFactory\Data\ColorEnum;
use ShipMonkTests\InputMapper\Compiler\MapperFactory\Data\InputWithDate;
use ShipMonkTests\InputMapper\Compiler\MapperFactory\Data\InputWithoutConstructor;
use ShipMonkTests\InputMapper\Compiler\MapperFactory\Data\InputWithPrivateConstructor;
use ShipMonkTests\InputMapper\InputMapperTestCase;

class DefaultMapperCompilerFactoryTest extends InputMapperTestCase
{

    /**
     * @param  array<string, mixed> $options
     */
    #[DataProvider('provideCreateOkData')]
    public function testCreateOk(string $type, array $options, MapperCompiler $expectedMapperCompiler): void
    {
        $phpDocLexer = new Lexer();
        $phpDocExprParser = new ConstExprParser(unescapeStrings: true);
        $phpDocTypeParser = new TypeParser($phpDocExprParser);
        $phpDocParser = new PhpDocParser($phpDocTypeParser, $phpDocExprParser);
        $phpDocType = $phpDocTypeParser->parse(new TokenIterator($phpDocLexer->tokenize($type)));

        $mapperCompilerFactory = new DefaultMapperCompilerFactory($phpDocLexer, $phpDocParser);
        $mapperCompiler = $mapperCompilerFactory->create($phpDocType, $options);

        self::assertEquals($expectedMapperCompiler, $mapperCompiler);
    }

    /**
     * @return iterable<array{string, array<string, mixed>, MapperCompiler}>
     */
    public static function provideCreateOkData(): iterable
    {
        yield 'CarInput' => [
            CarInput::class,
            [],
            new MapObject(CarInput::class, [
                'id' => new MapInt(),
                'name' => new ValidatedMapperCompiler(new MapString(), [new AssertStringLength(exact: 7)]),
                'brand' => new MapOptional(new DelegateMapperCompiler(BrandInput::class)),
                'numbers' => new MapList(new ValidatedMapperCompiler(new MapInt(), [new AssertPositiveInt()])),
            ]),
        ];

        yield 'CarInput with forced root delegation' => [
            CarInput::class,
            [DefaultMapperCompilerFactory::DELEGATE_OBJECT_MAPPING => true],
            new DelegateMapperCompiler(CarInput::class),
        ];

        yield 'BrandInput (with allowed extra keys)' => [
            BrandInput::class,
            [],
            new MapObject(
                BrandInput::class,
                ['name' => new MapString()],
                allowExtraKeys: true,
            ),
        ];

        yield 'ColorEnum' => [
            ColorEnum::class,
            [],
            new MapEnum(ColorEnum::class, new MapString()),
        ];

        yield 'DateTimeImmutable' => [
            DateTimeImmutable::class,
            [],
            new MapDateTimeImmutable(),
        ];

        yield 'DateTimeInterface' => [
            DateTimeInterface::class,
            [],
            new MapDateTimeImmutable(),
        ];

        yield 'InputWithDate' => [
            InputWithDate::class,
            [],
            new MapObject(InputWithDate::class, [
                'date' => new MapDateTimeImmutable('Y-m-d', 'date string in Y-m-d format'),
                'dateTime' => new DelegateMapperCompiler(DateTimeImmutable::class),
            ]),
        ];

        yield 'array' => [
            'array',
            [],
            new MapArray(new MapMixed(), new MapMixed()),
        ];

        yield 'bool' => [
            'bool',
            [],
            new MapBool(),
        ];

        yield 'int' => [
            'int',
            [],
            new MapInt(),
        ];

        yield 'float' => [
            'float',
            [],
            new MapFloat(),
        ];

        yield 'mixed' => [
            'mixed',
            [],
            new MapMixed(),
        ];

        yield 'string' => [
            'string',
            [],
            new MapString(),
        ];

        yield 'int[]' => [
            'int[]',
            [],
            new MapArray(new MapMixed(), new MapInt()),
        ];

        yield 'array<int>' => [
            'array<int>',
            [],
            new MapArray(new MapMixed(), new MapInt()),
        ];

        yield 'array<string, int>' => [
            'array<string, int>',
            [],
            new MapArray(new MapString(), new MapInt()),
        ];

        yield 'list' => [
            'list',
            [],
            new MapList(new MapMixed()),
        ];

        yield 'list<int>' => [
            'list<int>',
            [],
            new MapList(new MapInt()),
        ];

        yield 'int<0, 10>' => [
            'int<0, 10>',
            [],
            new ValidatedMapperCompiler(new MapInt(), [
                new AssertIntRange(gte: 0, lte: 10),
            ]),
        ];

        yield 'int<min, 10>' => [
            'int<min, 10>',
            [],
            new ValidatedMapperCompiler(new MapInt(), [
                new AssertIntRange(lte: 10),
            ]),
        ];

        yield 'int<0, max>' => [
            'int<0, max>',
            [],
            new ValidatedMapperCompiler(new MapInt(), [
                new AssertIntRange(gte: 0),
            ]),
        ];

        yield '?list<string>' => [
            '?list<string>',
            [],
            new MapNullable(new MapList(new MapString())),
        ];

        yield 'ShipMonk\InputMapper\Runtime\Optional<string>' => [
            'ShipMonk\InputMapper\Runtime\Optional<string>',
            [],
            new MapOptional(new MapString()),
        ];

        yield 'array sealed shape' => [
            'array{foo: string, bar: int}',
            [],
            new MapArrayShape(
                items: [
                    new ArrayShapeItemMapping('foo', new MapString()),
                    new ArrayShapeItemMapping('bar', new MapInt()),
                ],
                sealed: true,
            ),
        ];

        yield 'array unsealed shape' => [
            'array{foo: string, bar: int, ...}',
            [],
            new MapArrayShape(
                items: [
                    new ArrayShapeItemMapping('foo', new MapString()),
                    new ArrayShapeItemMapping('bar', new MapInt()),
                ],
                sealed: false,
            ),
        ];
    }

    /**
     * @param  array<string, mixed> $options
     */
    #[DataProvider('provideCreateErrorData')]
    public function testCreateError(string $type, array $options, ?string $expectedMessage = null): void
    {
        $phpDocLexer = new Lexer();
        $phpDocExprParser = new ConstExprParser(unescapeStrings: true);
        $phpDocTypeParser = new TypeParser($phpDocExprParser);
        $phpDocParser = new PhpDocParser($phpDocTypeParser, $phpDocExprParser);
        $phpDocType = $phpDocTypeParser->parse(new TokenIterator($phpDocLexer->tokenize($type)));

        $mapperCompilerFactory = new DefaultMapperCompilerFactory($phpDocLexer, $phpDocParser);

        self::assertException(
            CannotCreateMapperCompilerException::class,
            $expectedMessage,
            static fn() => $mapperCompilerFactory->create($phpDocType, $options),
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
            'Cannot create mapper for type ShipMonkTests\InputMapper\Compiler\MapperFactory\Data\InputWithoutConstructor, because class has no constructor',
        ];

        yield 'InputWithPrivateConstructor' => [
            InputWithPrivateConstructor::class,
            [],
            'Cannot create mapper for type ShipMonkTests\InputMapper\Compiler\MapperFactory\Data\InputWithPrivateConstructor, because class has a non-public constructor',
        ];

        yield 'DateTime' => [
            DateTime::class,
            [DefaultMapperCompilerFactory::DELEGATE_OBJECT_MAPPING => false],
        ];

        yield 'array<int, int, int>' => [
            'array<int, int, int>',
            [],
        ];

        yield 'List<int>' => [
            'List<int>',
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
    }

    public function testCreateWithCustomFactory(): void
    {
        $phpDocLexer = new Lexer();
        $phpDocExprParser = new ConstExprParser(unescapeStrings: true);
        $phpDocTypeParser = new TypeParser($phpDocExprParser);
        $phpDocParser = new PhpDocParser($phpDocTypeParser, $phpDocExprParser);

        $carMapperCompiler = new MapObject(CarInput::class, [
            'id' => new MapInt(),
            'name' => new MapString(),
            'brand' => new DelegateMapperCompiler(BrandInput::class),
        ]);

        $mapperCompilerFactory = new DefaultMapperCompilerFactory($phpDocLexer, $phpDocParser);

        $mapperCompilerFactory->setMapperCompilerFactory(CarInput::class, static function (string $inputClassName, array $options) use ($carMapperCompiler): MapperCompiler {
            self::assertSame(CarInput::class, $inputClassName);
            self::assertSame([], $options);

            return $carMapperCompiler;
        });

        $phpDocType = new IdentifierTypeNode(CarInput::class);
        self::assertSame($carMapperCompiler, $mapperCompilerFactory->create($phpDocType));
    }

}
