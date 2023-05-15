<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Runtime;

use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use ShipMonk\InputMapper\Runtime\Optional;
use ShipMonkTests\InputMapper\InputMapperTestCase;
use ShipMonkTests\InputMapper\Runtime\Data\DummyMapper;
use ShipMonkTests\InputMapper\Runtime\Data\EmptyInput;
use ShipMonkTests\InputMapper\Runtime\Data\InputInterface;
use ShipMonkTests\InputMapper\Runtime\Data\InterfaceImplementationInput;
use ShipMonkTests\InputMapper\Runtime\Data\Optional\OptionalNotNullInput;
use ShipMonkTests\InputMapper\Runtime\Data\Optional\OptionalNullableInput;
use function sys_get_temp_dir;

class MapperProviderTest extends InputMapperTestCase
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

    public function testMapperForOptionalNotNullInput(): void
    {
        $mapperProvider = $this->createMapperProvider();
        $mapper = $mapperProvider->get(OptionalNotNullInput::class);
        self::assertEquals(new OptionalNotNullInput(Optional::of(123)), $mapper->map(['number' => 123]));
        self::assertEquals(new OptionalNotNullInput(Optional::none([], 'number')), $mapper->map([]));

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /number: Expected int, got null',
            static function () use ($mapper): void {
                $mapper->map(['number' => null]);
            },
        );
    }

    public function testMapperForOptionalNullableInput(): void
    {
        $mapperProvider = $this->createMapperProvider();
        $mapper = $mapperProvider->get(OptionalNullableInput::class);
        self::assertEquals(new OptionalNullableInput(Optional::of(123)), $mapper->map(['number' => 123]));
        self::assertEquals(new OptionalNullableInput(Optional::of(null)), $mapper->map(['number' => null]));
        self::assertEquals(new OptionalNullableInput(Optional::none([], 'number')), $mapper->map([]));
    }

    private function createMapperProvider(): MapperProvider
    {
        $tempDir = sys_get_temp_dir();
        return new MapperProvider($tempDir, autoRefresh: true);
    }

}
