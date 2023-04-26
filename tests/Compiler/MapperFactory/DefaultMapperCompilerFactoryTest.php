<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\MapperFactory;

use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPUnit\Framework\Attributes\DataProvider;
use ShipMonk\InputMapper\Compiler\Mapper\Array\MapArray;
use ShipMonk\InputMapper\Compiler\Mapper\Array\MapList;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Mixed\MapMixed;
use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapBool;
use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapFloat;
use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapInt;
use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapString;
use ShipMonk\InputMapper\Compiler\Mapper\Wrapper\MapNullable;
use ShipMonk\InputMapper\Compiler\Mapper\Wrapper\MapOptional;
use ShipMonk\InputMapper\Compiler\Mapper\Wrapper\ValidatedMapperCompiler;
use ShipMonk\InputMapper\Compiler\MapperFactory\DefaultMapperCompilerFactory;
use ShipMonk\InputMapper\Compiler\Validator\Int\AssertIntRange;
use ShipMonkTests\InputMapper\InputMapperTestCase;

class DefaultMapperCompilerFactoryTest extends InputMapperTestCase
{

    #[DataProvider('provideCreateData')]
    public function testCreate(string $type, array $options, MapperCompiler $expectedMapperCompiler): void
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
    public static function provideCreateData(): iterable
    {
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
    }

}
