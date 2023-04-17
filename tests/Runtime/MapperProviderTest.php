<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Tests\Runtime;

use PHPUnit\Framework\TestCase;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use ShipMonkTests\InputMapper\Tests\Runtime\Data\DummyMapper;
use ShipMonkTests\InputMapper\Tests\Runtime\Data\EmptyInput;
use ShipMonkTests\InputMapper\Tests\Runtime\Data\InputInterface;
use ShipMonkTests\InputMapper\Tests\Runtime\Data\InterfaceImplementationInput;
use function sys_get_temp_dir;

class MapperProviderTest extends TestCase
{

    public function testGetMapperForEmptyInput(): void
    {
        $mapperProvider = $this->createMapperProvider();
        $mapper = $mapperProvider->get(EmptyInput::class);
        self::assertInstanceOf(Mapper::class, $mapper); // @phpstan-ignore-line always true
        self::assertInstanceOf(EmptyInput::class, $mapper->map([])); // @phpstan-ignore-line always true
        self::assertSame($mapper, $mapperProvider->get(EmptyInput::class));
    }

    public function testGetCustomMapperForEmptyInput(): void
    {
        $myCustomMapper = new DummyMapper();

        $mapperProvider = $this->createMapperProvider();
        $mapperProvider->registerFactory(
            EmptyInput::class,
            static function (string $inputClassName, MapperProvider $provider) use ($myCustomMapper, $mapperProvider): DummyMapper {
                self::assertSame(EmptyInput::class, $inputClassName);
                self::assertSame($mapperProvider, $provider);
                return $myCustomMapper;
            },
        );

        self::assertSame($myCustomMapper, $mapperProvider->get(EmptyInput::class));
        self::assertSame($myCustomMapper, $mapperProvider->get(EmptyInput::class));
    }

    public function testGetCustomMapperForInterfaceImplementationInput(): void
    {
        $myCustomMapper = new DummyMapper();

        $mapperProvider = $this->createMapperProvider();
        $mapperProvider->registerFactory(
            InputInterface::class,
            static function (string $inputClassName, MapperProvider $provider) use ($myCustomMapper, $mapperProvider): DummyMapper {
                self::assertSame(InterfaceImplementationInput::class, $inputClassName);
                self::assertSame($mapperProvider, $provider);
                return $myCustomMapper;
            },
        );

        self::assertSame($myCustomMapper, $mapperProvider->get(InterfaceImplementationInput::class));
        self::assertSame($myCustomMapper, $mapperProvider->get(InterfaceImplementationInput::class));
    }

    private function createMapperProvider(): MapperProvider
    {
        $tempDir = sys_get_temp_dir();
        return new MapperProvider($tempDir, autoRefresh: true);
    }

}
