<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Codec;

use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use ShipMonk\InputMapper\Compiler\Mapper\Codec\InlineCodecInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Input\StringInputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapperTests\Compiler\Mapper\Codec\Data\CurrencyPrefixCodec;
use ShipMonk\InputMapperTests\Compiler\Mapper\MapperCompilerTestCase;

class InlineCodecInputMapperCompilerTest extends MapperCompilerTestCase
{

    public function testCompile(): void
    {
        $mapper = $this->compileInputMapper(
            'InlineCodecCurrencyPrefix',
            new InlineCodecInputMapperCompiler(
                CurrencyPrefixCodec::class,
                ['prefix' => 'USD', 'separator' => ':'],
                new StringInputMapperCompiler(),
                new IdentifierTypeNode('string'),
            ),
        );

        self::assertSame('1299', $mapper->map('USD:1299'));
    }

    public function testCompileWithDifferentArgs(): void
    {
        $mapper = $this->compileInputMapper(
            'InlineCodecCurrencyPrefixEur',
            new InlineCodecInputMapperCompiler(
                CurrencyPrefixCodec::class,
                ['prefix' => 'EUR', 'separator' => '-'],
                new StringInputMapperCompiler(),
                new IdentifierTypeNode('string'),
            ),
        );

        self::assertSame('500', $mapper->map('EUR-500'));
    }

    public function testCompileWithInvalidInput(): void
    {
        $mapper = $this->compileInputMapper(
            'InlineCodecCurrencyPrefix',
            new InlineCodecInputMapperCompiler(
                CurrencyPrefixCodec::class,
                ['prefix' => 'USD', 'separator' => ':'],
                new StringInputMapperCompiler(),
                new IdentifierTypeNode('string'),
            ),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected string, got 123',
            static fn () => $mapper->map(123),
        );
    }

    public function testCompileWithCodecValidation(): void
    {
        $mapper = $this->compileInputMapper(
            'InlineCodecCurrencyPrefix',
            new InlineCodecInputMapperCompiler(
                CurrencyPrefixCodec::class,
                ['prefix' => 'USD', 'separator' => ':'],
                new StringInputMapperCompiler(),
                new IdentifierTypeNode('string'),
            ),
        );

        self::assertException(
            MappingFailedException::class,
            "Failed to map data at path /: Expected string starting with 'USD:', got \"EUR:500\"",
            static fn () => $mapper->map('EUR:500'),
        );
    }

}
