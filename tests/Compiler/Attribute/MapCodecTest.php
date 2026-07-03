<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Attribute;

use ShipMonk\InputMapper\Compiler\Attribute\MapCodec;
use ShipMonk\InputMapper\Compiler\Exception\CannotCreateMapperCompilerException;
use ShipMonk\InputMapper\Compiler\Mapper\Codec\InlineCodecInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Codec\InlineCodecOutputMapperCompiler;
use ShipMonk\InputMapperTests\Compiler\Mapper\Codec\Data\CurrencyPrefixCodec;
use ShipMonk\InputMapperTests\Compiler\Mapper\Codec\Data\ObjectArgCodec;
use ShipMonk\InputMapperTests\InputMapperTestCase;
use ShipMonk\InputMapperTests\Runtime\Data\MoneyValue;

class MapCodecTest extends InputMapperTestCase
{

    public function testGetInputMapperCompiler(): void
    {
        $factory = self::createMapperCompilerFactory();
        $mapCodec = new MapCodec(new CurrencyPrefixCodec('EUR', '-'));

        $mapperCompiler = $mapCodec->getInputMapperCompiler($factory, []);

        self::assertInstanceOf(InlineCodecInputMapperCompiler::class, $mapperCompiler);
        self::assertSame(CurrencyPrefixCodec::class, $mapperCompiler->codecClassName);
        self::assertSame(['prefix' => 'EUR', 'separator' => '-'], $mapperCompiler->constructorArgs);
    }

    public function testGetOutputMapperCompiler(): void
    {
        $factory = self::createMapperCompilerFactory();
        $mapCodec = new MapCodec(new CurrencyPrefixCodec('EUR', '-'));

        $mapperCompiler = $mapCodec->getOutputMapperCompiler($factory, []);

        self::assertInstanceOf(InlineCodecOutputMapperCompiler::class, $mapperCompiler);
        self::assertSame(CurrencyPrefixCodec::class, $mapperCompiler->codecClassName);
        self::assertSame(['prefix' => 'EUR', 'separator' => '-'], $mapperCompiler->constructorArgs);
    }

    public function testNonScalarConstructorArgumentIsRejected(): void
    {
        $factory = self::createMapperCompilerFactory();
        $codec = new ObjectArgCodec(new MoneyValue('USD', 1));

        self::assertException(
            CannotCreateMapperCompilerException::class,
            'Cannot compile codec %s inline, because constructor argument $money must be a scalar or null value, got %s',
            static fn () => (new MapCodec($codec))->getInputMapperCompiler($factory, []),
        );
    }

}
