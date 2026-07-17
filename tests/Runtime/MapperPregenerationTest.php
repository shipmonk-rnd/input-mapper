<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Runtime;

use Nette\Utils\FileSystem;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use ShipMonk\InputMapperTests\InputMapperTestCase;
use ShipMonk\InputMapperTests\Runtime\Data\DummyMapper;
use ShipMonk\InputMapperTests\Runtime\Data\Pregeneration\AddressInput;
use ShipMonk\InputMapperTests\Runtime\Data\Pregeneration\CustomerInput;
use ShipMonk\InputMapperTests\Runtime\Data\Pregeneration\ItemInput;
use ShipMonk\InputMapperTests\Runtime\Data\Pregeneration\OrderInput;
use ShipMonk\InputMapperTests\Runtime\Data\Pregeneration\PriceInput;
use function getmypid;
use function md5;
use function strrpos;
use function substr;
use function sys_get_temp_dir;

class MapperPregenerationTest extends InputMapperTestCase
{

    public function testPregenerateGeneratesMappersForAllDelegatedClasses(): void
    {
        $tempDir = self::createTempDir(__FUNCTION__);

        try {
            $provider = new MapperProvider($tempDir);
            $generated = $provider->pregenerate([OrderInput::class]);

            self::assertEqualsCanonicalizing(
                [OrderInput::class, CustomerInput::class, ItemInput::class, AddressInput::class, PriceInput::class],
                $generated,
            );

            foreach ($generated as $className) {
                self::assertFileExists(self::getMapperPath($tempDir, $className, 'input'));
                self::assertFileExists(self::getMapperPath($tempDir, $className, 'output'));
            }

            $order = $provider->getInputMapper(OrderInput::class)->map([
                'customer' => ['name' => 'John', 'address' => ['city' => 'Prague']],
                'items' => [['name' => 'Socks', 'price' => ['amount' => 5, 'currency' => 'EUR']]],
            ]);

            self::assertSame('Prague', $order->customer->address->city);
            self::assertSame('EUR', $order->items[0]->price->currency);
        } finally {
            FileSystem::delete($tempDir);
        }
    }

    public function testPregenerateDiscoversDelegatedClassesEvenWithWarmCache(): void
    {
        $tempDir = self::createTempDir(__FUNCTION__);

        try {
            $coldGenerated = (new MapperProvider($tempDir))->pregenerate([OrderInput::class]);
            $warmGenerated = (new MapperProvider($tempDir))->pregenerate([OrderInput::class]);

            self::assertEqualsCanonicalizing($coldGenerated, $warmGenerated);
        } finally {
            FileSystem::delete($tempDir);
        }
    }

    public function testPregenerateSkipsClassesWithRegisteredFactory(): void
    {
        $tempDir = self::createTempDir(__FUNCTION__);

        try {
            $provider = new MapperProvider($tempDir);
            $provider->registerInputFactory(PriceInput::class, static fn () => new DummyMapper());
            $provider->registerOutputFactory(PriceInput::class, static fn () => new DummyMapper());

            $generated = $provider->pregenerate([OrderInput::class]);

            self::assertEqualsCanonicalizing(
                [OrderInput::class, CustomerInput::class, ItemInput::class, AddressInput::class],
                $generated,
            );

            self::assertFileDoesNotExist(self::getMapperPath($tempDir, PriceInput::class, 'input'));
            self::assertFileDoesNotExist(self::getMapperPath($tempDir, PriceInput::class, 'output'));
        } finally {
            FileSystem::delete($tempDir);
        }
    }

    private static function createTempDir(string $testName): string
    {
        return sys_get_temp_dir() . '/input-mapper-test-pregeneration-' . $testName . '-' . getmypid();
    }

    /**
     * @param class-string $className
     * @param 'input'|'output' $direction
     */
    private static function getMapperPath(
        string $tempDir,
        string $className,
        string $direction,
    ): string
    {
        $pos = strrpos($className, '\\');
        $shortName = $pos === false ? $className : substr($className, $pos + 1);
        $hash = substr(md5($className), 0, 8);
        $suffix = $direction === 'input' ? 'Mapper' : 'OutputMapper';

        return "{$tempDir}/{$shortName}{$suffix}_{$hash}.php";
    }

}
