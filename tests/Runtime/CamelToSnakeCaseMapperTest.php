<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Runtime;

use ShipMonk\InputMapper\Compiler\MapperFactory\DefaultMapperCompilerFactoryProvider;
use ShipMonk\InputMapper\Compiler\PropertyNameTransformer\CamelToSnakeCasePropertyNameTransformer;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use ShipMonk\InputMapperTests\InputMapperTestCase;
use ShipMonk\InputMapperTests\Runtime\Data\AcronymCasedInput;
use ShipMonk\InputMapperTests\Runtime\Data\CamelCasePropertiesInput;
use ShipMonk\InputMapperTests\Runtime\Data\CamelCaseWithSourceKeyInput;
use function sys_get_temp_dir;

class CamelToSnakeCaseMapperTest extends InputMapperTestCase
{

    public function testCamelToSnakeCaseTransformerConvertsSimpleNames(): void
    {
        $transformer = new CamelToSnakeCasePropertyNameTransformer();
        self::assertSame('first_name', $transformer->transform('firstName'));
        self::assertSame('user_id', $transformer->transform('userId'));
        self::assertSame('id', $transformer->transform('id'));
        self::assertSame('url', $transformer->transform('url'));
        self::assertSame('a', $transformer->transform('a'));
    }

    public function testCamelToSnakeCaseTransformerHandlesAcronyms(): void
    {
        $transformer = new CamelToSnakeCasePropertyNameTransformer();
        self::assertSame('http_server', $transformer->transform('HTTPServer'));
        self::assertSame('user_id_value', $transformer->transform('userIDValue'));
        self::assertSame('parse_url', $transformer->transform('parseURL'));
    }

    public function testRoundTripAppliesSnakeCaseInBothDirections(): void
    {
        $provider = $this->createProvider();

        $snakeCaseData = ['user_id' => 1, 'first_name' => 'John', 'last_name' => 'Doe'];
        $object = $provider->getInputMapper(CamelCasePropertiesInput::class)->map($snakeCaseData);

        self::assertSame(1, $object->userId);
        self::assertSame('John', $object->firstName);
        self::assertSame('Doe', $object->lastName);

        self::assertSame($snakeCaseData, $provider->getOutputMapper(CamelCasePropertiesInput::class)->map($object));
    }

    public function testSourceKeyOverrideIsNotTransformed(): void
    {
        $provider = $this->createProvider();

        $data = ['user_id' => 42, 'CustomKey' => 'Alice'];
        $object = $provider->getInputMapper(CamelCaseWithSourceKeyInput::class)->map($data);

        self::assertSame(42, $object->userId);
        self::assertSame('Alice', $object->firstName);

        self::assertSame($data, $provider->getOutputMapper(CamelCaseWithSourceKeyInput::class)->map($object));
    }

    public function testAcronymProperties(): void
    {
        $provider = $this->createProvider();

        $data = ['http_server' => 'nginx', 'user_id_value' => 7];
        $object = $provider->getInputMapper(AcronymCasedInput::class)->map($data);

        self::assertSame('nginx', $object->HTTPServer);
        self::assertSame(7, $object->userIDValue);

        self::assertSame($data, $provider->getOutputMapper(AcronymCasedInput::class)->map($object));
    }

    private function createProvider(): MapperProvider
    {
        return new MapperProvider(
            sys_get_temp_dir(),
            autoRefresh: true,
            mapperCompilerFactoryProvider: new DefaultMapperCompilerFactoryProvider(
                new CamelToSnakeCasePropertyNameTransformer(),
            ),
        );
    }

}
