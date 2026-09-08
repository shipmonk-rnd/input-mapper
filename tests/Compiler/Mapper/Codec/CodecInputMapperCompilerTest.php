<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Codec;

use ShipMonk\InputMapper\Compiler\Mapper\Codec\CodecInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Input\ArrayShapeInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Input\IntInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Input\StringInputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapperTests\Compiler\Mapper\MapperCompilerTestCase;
use ShipMonk\InputMapperTests\Runtime\Data\MoneyCodec;
use ShipMonk\InputMapperTests\Runtime\Data\MoneyValue;

class CodecInputMapperCompilerTest extends MapperCompilerTestCase
{

    public function testCompile(): void
    {
        $mapper = $this->compileInputMapper(
            'CodecMoney',
            new CodecInputMapperCompiler(MoneyCodec::class, $this->createIntermediateInputMapper(), MoneyValue::class),
            codecs: [MoneyCodec::class => new MoneyCodec()],
        );

        /** @var MoneyValue $result */
        $result = $mapper->map(['currency' => 'USD', 'cents' => 1_299]);

        self::assertSame('USD', $result->currency);
        self::assertSame(1_299, $result->cents);
    }

    public function testCompileWithInvalidInput(): void
    {
        $mapper = $this->compileInputMapper(
            'CodecMoney',
            new CodecInputMapperCompiler(MoneyCodec::class, $this->createIntermediateInputMapper(), MoneyValue::class),
            codecs: [MoneyCodec::class => new MoneyCodec()],
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected array, got "invalid"',
            static fn () => $mapper->map('invalid'),
        );
    }

    public function testCompileWithDecodeValidation(): void
    {
        $mapper = $this->compileInputMapper(
            'CodecMoney',
            new CodecInputMapperCompiler(MoneyCodec::class, $this->createIntermediateInputMapper(), MoneyValue::class),
            codecs: [MoneyCodec::class => new MoneyCodec()],
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /cents: Expected non-negative integer, got -1',
            static fn () => $mapper->map(['currency' => 'USD', 'cents' => -1]),
        );
    }

    private function createIntermediateInputMapper(): ArrayShapeInputMapperCompiler
    {
        return new ArrayShapeInputMapperCompiler([
            ['key' => 'currency', 'mapper' => new StringInputMapperCompiler(), 'optional' => false],
            ['key' => 'cents', 'mapper' => new IntInputMapperCompiler(), 'optional' => false],
        ]);
    }

}
