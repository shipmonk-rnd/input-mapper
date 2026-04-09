<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Runtime;

use Nette\Utils\FileSystem;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use ShipMonk\InputMapper\Compiler\MapperFactory\DefaultMapperCompilerFactoryProvider;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Compiler\Php\PhpCodePrinter;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use ShipMonk\InputMapper\Runtime\Optional;
use ShipMonk\InputMapperTests\InputMapperTestCase;
use ShipMonk\InputMapperTests\Runtime\Data\CacheTestInput;
use ShipMonk\InputMapperTests\Runtime\Data\DummyMapper;
use ShipMonk\InputMapperTests\Runtime\Data\EmptyInput;
use ShipMonk\InputMapperTests\Runtime\Data\InputInterface;
use ShipMonk\InputMapperTests\Runtime\Data\InterfaceImplementationInput;
use ShipMonk\InputMapperTests\Runtime\Data\MkdirTestInput;
use ShipMonk\InputMapperTests\Runtime\Data\Optional\OptionalNotNullInput;
use ShipMonk\InputMapperTests\Runtime\Data\Optional\OptionalNullableInput;
use function getmypid;
use function md5;
use function mkdir;
use function substr;
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

    public function testLoadFromCachedFile(): void
    {
        $tempDir = sys_get_temp_dir() . '/input-mapper-test-cache-' . getmypid();
        @mkdir($tempDir, recursive: true);

        try {
            // Pre-compile the mapper file to disk WITHOUT loading the class into PHP memory
            $className = CacheTestInput::class;
            $shortName = 'CacheTestInput';
            $hash = substr(md5($className), 0, 8);
            $mapperClassName = "ShipMonk\\InputMapper\\Runtime\\Generated\\{$shortName}Mapper_{$hash}";
            $filePath = "{$tempDir}/{$shortName}Mapper_{$hash}.php";

            $factoryProvider = new DefaultMapperCompilerFactoryProvider();
            $factory = $factoryProvider->get();
            $mapperCompiler = $factory->create(new IdentifierTypeNode($className))->getInputMapperCompiler();

            $codeBuilder = new PhpCodeBuilder();
            $codePrinter = new PhpCodePrinter();
            $code = $codePrinter->prettyPrintFile($codeBuilder->inputMapperFile($mapperClassName, $mapperCompiler));
            FileSystem::write($filePath, $code);

            // Now load from cache with autoRefresh=false
            $provider = new MapperProvider($tempDir, autoRefresh: false);
            $mapper = $provider->getInputMapper(CacheTestInput::class);
            self::assertInstanceOf(CacheTestInput::class, $mapper->map([])); // @phpstan-ignore-line always true
        } finally {
            FileSystem::delete($tempDir);
        }
    }

    public function testLoadCreatesDirectoryWhenMissing(): void
    {
        $tempDir = sys_get_temp_dir() . '/input-mapper-test-mkdir-' . getmypid();

        try {
            $provider = new MapperProvider($tempDir, autoRefresh: true);
            $mapper = $provider->getInputMapper(MkdirTestInput::class);
            self::assertInstanceOf(MkdirTestInput::class, $mapper->map([])); // @phpstan-ignore-line always true
        } finally {
            FileSystem::delete($tempDir);
        }
    }

    private function createMapperProvider(): MapperProvider
    {
        $tempDir = sys_get_temp_dir();
        return new MapperProvider($tempDir, autoRefresh: true);
    }

}
