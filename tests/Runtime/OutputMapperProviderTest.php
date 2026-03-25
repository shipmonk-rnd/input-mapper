<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Runtime;

use ShipMonk\InputMapper\Runtime\MapperProvider;
use ShipMonk\InputMapperTests\InputMapperTestCase;
use ShipMonk\InputMapperTests\Runtime\Data\DummyOutputMapper;
use ShipMonk\InputMapperTests\Runtime\Data\EmptyInput;
use ShipMonk\InputMapperTests\Runtime\Data\InputInterface;
use ShipMonk\InputMapperTests\Runtime\Data\InterfaceImplementationInput;
use function sys_get_temp_dir;

class OutputMapperProviderTest extends InputMapperTestCase
{

    public function testGetCustomMapperForEmptyInput(): void
    {
        $myCustomMapper = new DummyOutputMapper();

        $mapperProvider = $this->createMapperProvider();
        $mapperProvider->registerOutputFactory(
            EmptyInput::class,
            static function (string $inputClassName, array $innerMappers, MapperProvider $provider) use ($myCustomMapper, $mapperProvider): DummyOutputMapper {
                self::assertSame(EmptyInput::class, $inputClassName);
                self::assertSame($mapperProvider, $provider);
                return $myCustomMapper;
            },
        );

        self::assertSame($myCustomMapper, $mapperProvider->getOutputMapper(EmptyInput::class));
        self::assertSame($myCustomMapper, $mapperProvider->getOutputMapper(EmptyInput::class));
    }

    public function testGetCustomMapperForInterfaceImplementationInput(): void
    {
        $myCustomMapper = new DummyOutputMapper();

        $mapperProvider = $this->createMapperProvider();
        $mapperProvider->registerOutputFactory(
            InputInterface::class,
            static function (string $inputClassName, array $innerMappers, MapperProvider $provider) use ($myCustomMapper, $mapperProvider): DummyOutputMapper {
                self::assertSame(InterfaceImplementationInput::class, $inputClassName);
                self::assertSame($mapperProvider, $provider);
                return $myCustomMapper;
            },
        );

        self::assertSame($myCustomMapper, $mapperProvider->getOutputMapper(InterfaceImplementationInput::class));
        self::assertSame($myCustomMapper, $mapperProvider->getOutputMapper(InterfaceImplementationInput::class));
    }

    public function testCachingReturnsSameInstance(): void
    {
        $myCustomMapper = new DummyOutputMapper();
        $callCount = 0;

        $mapperProvider = $this->createMapperProvider();
        $mapperProvider->registerOutputFactory(
            EmptyInput::class,
            static function () use ($myCustomMapper, &$callCount): DummyOutputMapper {
                $callCount++;
                return $myCustomMapper;
            },
        );

        $mapper1 = $mapperProvider->getOutputMapper(EmptyInput::class);
        $mapper2 = $mapperProvider->getOutputMapper(EmptyInput::class);

        self::assertSame($mapper1, $mapper2);
        self::assertSame(1, $callCount);
    }

    private function createMapperProvider(): MapperProvider
    {
        $tempDir = sys_get_temp_dir();
        return new MapperProvider($tempDir, autoRefresh: true);
    }

}
