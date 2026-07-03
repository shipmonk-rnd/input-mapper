<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Codec;

use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use ShipMonk\InputMapper\Compiler\Mapper\Codec\InlineCodecOutputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\PassthroughMapperCompiler;
use ShipMonk\InputMapperTests\Compiler\Mapper\Codec\Data\CurrencyPrefixCodec;
use ShipMonk\InputMapperTests\Compiler\Mapper\MapperCompilerTestCase;

class InlineCodecOutputMapperCompilerTest extends MapperCompilerTestCase
{

    public function testCompile(): void
    {
        $mapper = $this->compileOutputMapper(
            'InlineCodecCurrencyPrefix',
            new InlineCodecOutputMapperCompiler(
                CurrencyPrefixCodec::class,
                ['prefix' => 'USD', 'separator' => ':'],
                new PassthroughMapperCompiler(new IdentifierTypeNode('string')),
                new IdentifierTypeNode('string'),
            ),
        );

        self::assertSame('USD:1299', $mapper->map('1299'));
    }

    public function testCompileWithDifferentArgs(): void
    {
        $mapper = $this->compileOutputMapper(
            'InlineCodecCurrencyPrefixEur',
            new InlineCodecOutputMapperCompiler(
                CurrencyPrefixCodec::class,
                ['prefix' => 'EUR', 'separator' => '-'],
                new PassthroughMapperCompiler(new IdentifierTypeNode('string')),
                new IdentifierTypeNode('string'),
            ),
        );

        self::assertSame('EUR-500', $mapper->map('500'));
    }

}
