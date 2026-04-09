<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Runtime;

use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use ShipMonk\InputMapper\Runtime\Optional;
use ShipMonk\InputMapperTests\InputMapperTestCase;
use ShipMonk\InputMapperTests\Runtime\Data\DummyMapper;
use ShipMonk\InputMapperTests\Runtime\Data\EmptyInput;
use ShipMonk\InputMapperTests\Runtime\Data\InputInterface;
use ShipMonk\InputMapperTests\Runtime\Data\InterfaceImplementationInput;
use ShipMonk\InputMapperTests\Runtime\Data\Optional\OptionalNotNullInput;
use ShipMonk\InputMapperTests\Runtime\Data\Optional\OptionalNullableInput;
use function sys_get_temp_dir;

class InputMapperProviderTest extends InputMapperTestCase
{

    public function testGetMapperForEmptyInput(): void
    {
        $mapperProvider = $this->createMapperProvider();
        $mapper = $mapperProvider->getInputMapper(EmptyInput::class);
        self::assertInstanceOf(Mapper::class, $mapper); // @phpstan-ignore-line always true
        self::assertInstanceOf(EmptyInput::class, $mapper->map([])); // @phpstan-ignore-line always true
        self::assertSame($mapper, $mapperProvider->getInputMapper(EmptyInput::class));
    }

    public function testGetCustomMapperForEmptyInput(): void
    {
        $myCustomMapper = new DummyMapper();

        $mapperProvider = $this->createMapperProvider();
        $mapperProvider->registerInputFactory(
            EmptyInput::class,
            static function (string $inputClassName, array $genericInnerMappers, MapperProvider $provider) use ($myCustomMapper, $mapperProvider): DummyMapper {
                self::assertSame(EmptyInput::class, $inputClassName);
                self::assertSame($mapperProvider, $provider);
                return $myCustomMapper;
            },
        );

        self::assertSame($myCustomMapper, $mapperProvider->getInputMapper(EmptyInput::class));
        self::assertSame($myCustomMapper, $mapperProvider->getInputMapper(EmptyInput::class));
    }

    public function testGetCustomMapperForInterfaceImplementationInput(): void
    {
        $myCustomMapper = new DummyMapper();

        $mapperProvider = $this->createMapperProvider();
        $mapperProvider->registerInputFactory(
            InputInterface::class,
            static function (string $inputClassName, array $genericInnerMappers, MapperProvider $provider) use ($myCustomMapper, $mapperProvider): DummyMapper {
                self::assertSame(InterfaceImplementationInput::class, $inputClassName);
                self::assertSame($mapperProvider, $provider);
                return $myCustomMapper;
            },
        );

        self::assertSame($myCustomMapper, $mapperProvider->getInputMapper(InterfaceImplementationInput::class));
        self::assertSame($myCustomMapper, $mapperProvider->getInputMapper(InterfaceImplementationInput::class));
    }

    public function testMapperForOptionalNotNullInput(): void
    {
        $mapperProvider = $this->createMapperProvider();
        $mapper = $mapperProvider->getInputMapper(OptionalNotNullInput::class);
        self::assertEquals(new OptionalNotNullInput(Optional::of(123)), $mapper->map(['number' => 123]));
        self::assertEquals(new OptionalNotNullInput(Optional::none([], 'number')), $mapper->map([]));

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /number: Expected int, got null',
            static function () use ($mapper): void {
                $mapper->map(['number' => null]);
            },
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /number: Expected value greater than 0, got -1',
            static function () use ($mapper): void {
                $mapper->map(['number' => -1]);
            },
        );
    }

    public function testMapperForOptionalNullableInput(): void
    {
        $mapperProvider = $this->createMapperProvider();
        $mapper = $mapperProvider->getInputMapper(OptionalNullableInput::class);
        self::assertEquals(new OptionalNullableInput(Optional::of(123)), $mapper->map(['number' => 123]));
        self::assertEquals(new OptionalNullableInput(Optional::of(null)), $mapper->map(['number' => null]));
        self::assertEquals(new OptionalNullableInput(Optional::none([], 'number')), $mapper->map([]));

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /number: Expected value greater than 0, got -1',
            static function () use ($mapper): void {
                $mapper->map(['number' => -1]);
            },
        );
    }

    private function createMapperProvider(): MapperProvider
    {
        $tempDir = sys_get_temp_dir();
        return new MapperProvider($tempDir, autoRefresh: true);
    }

}
