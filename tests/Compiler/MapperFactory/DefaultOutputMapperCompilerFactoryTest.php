<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\MapperFactory;

use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\PhpDocParser\ParserConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use ShipMonk\InputMapper\Compiler\Exception\CannotCreateMapperCompilerException;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Output\ArrayOutputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Output\ArrayShapeOutputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Output\DelegateOutputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Output\DiscriminatedObjectOutputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Output\ListOutputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Output\ObjectOutputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\PassthroughMapperCompiler;
use ShipMonk\InputMapper\Compiler\MapperFactory\DefaultOutputMapperCompilerFactory;
use ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data\HierarchicalChildOneInput;
use ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data\HierarchicalChildTwoInput;
use ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data\HierarchicalParentInput;
use ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data\SimplePersonInput;
use ShipMonk\InputMapperTests\Compiler\MapperFactory\Data\InputWithRenamedSourceKey;
use ShipMonk\InputMapperTests\InputMapperTestCase;

class DefaultOutputMapperCompilerFactoryTest extends InputMapperTestCase
{

    /**
     * @param array<string, mixed> $options
     */
    #[DataProvider('provideCreateOkData')]
    public function testCreateOk(
        string $type,
        array $options,
        MapperCompiler $expectedMapperCompiler,
    ): void
    {
        $config = new ParserConfig([]);
        $phpDocLexer = new Lexer($config);
        $phpDocConstExprParser = new ConstExprParser($config);
        $phpDocTypeParser = new TypeParser($config, $phpDocConstExprParser);
        $phpDocParser = new PhpDocParser($config, $phpDocTypeParser, $phpDocConstExprParser);
        $phpDocType = $phpDocTypeParser->parse(new TokenIterator($phpDocLexer->tokenize($type)));

        $mapperCompilerFactory = new DefaultOutputMapperCompilerFactory($phpDocLexer, $phpDocParser);
        $mapperCompiler = $mapperCompilerFactory->create($phpDocType, $options);

        self::assertEquals($expectedMapperCompiler, $mapperCompiler);
    }

    /**
     * @return iterable<array{string, array<string, mixed>, MapperCompiler}>
     */
    public static function provideCreateOkData(): iterable
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
                'type',
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
        string $expectedMessage,
    ): void
    {
        $config = new ParserConfig([]);
        $phpDocLexer = new Lexer($config);
        $phpDocConstExprParser = new ConstExprParser($config);
        $phpDocTypeParser = new TypeParser($config, $phpDocConstExprParser);
        $phpDocParser = new PhpDocParser($config, $phpDocTypeParser, $phpDocConstExprParser);
        $phpDocType = $phpDocTypeParser->parse(new TokenIterator($phpDocLexer->tokenize($type)));

        $mapperCompilerFactory = new DefaultOutputMapperCompilerFactory($phpDocLexer, $phpDocParser);

        self::assertException(
            CannotCreateMapperCompilerException::class,
            $expectedMessage,
            static fn () => $mapperCompilerFactory->create($phpDocType, $options),
        );
    }

    /**
     * @return iterable<array{string, array<string, mixed>, string}>
     */
    public static function provideCreateErrorData(): iterable
    {
        yield 'list' => [
            'list',
            [],
            'Cannot create mapper for type list',
        ];

        yield 'array' => [
            'array',
            [],
            'Cannot create mapper for type array',
        ];
    }

}
