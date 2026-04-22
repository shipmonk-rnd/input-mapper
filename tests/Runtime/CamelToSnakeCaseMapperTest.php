<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Runtime;

use PHPUnit\Framework\Attributes\DataProvider;
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

    #[DataProvider('provideTransformData')]
    public function testTransform(
        string $input,
        string $expected,
    ): void
    {
        $transformer = new CamelToSnakeCasePropertyNameTransformer();
        self::assertSame($expected, $transformer->transform($input));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideTransformData(): iterable
    {
        // simple camelCase
        yield 'camelCase' => ['camelCase', 'camel_case'];
        yield 'firstName' => ['firstName', 'first_name'];
        yield 'userId' => ['userId', 'user_id'];
        yield 'userName' => ['userName', 'user_name'];

        // leading uppercase (PascalCase)
        yield 'FirstName' => ['FirstName', 'first_name'];

        // already lowercase / single word
        yield 'id' => ['id', 'id'];
        yield 'url' => ['url', 'url'];
        yield 'a' => ['a', 'a'];

        // single uppercase letter
        yield 'A' => ['A', 'a'];

        // whole identifier is an acronym
        yield 'ID' => ['ID', 'id'];
        yield 'API' => ['API', 'api'];

        // acronym followed by a Word
        yield 'HTTPServer' => ['HTTPServer', 'http_server'];
        yield 'URLParser' => ['URLParser', 'url_parser'];

        // word followed by trailing acronym
        yield 'parseURL' => ['parseURL', 'parse_url'];
        yield 'userID' => ['userID', 'user_id'];

        // acronym in the middle
        yield 'userIDValue' => ['userIDValue', 'user_id_value'];

        // two-letter acronym at start
        yield 'IOError' => ['IOError', 'io_error'];

        // trailing digit glued to preceding word
        yield 'htmlParser5' => ['htmlParser5', 'html_parser5'];
        yield 'Version2' => ['Version2', 'version2'];

        // digit treated as lowercase — splits before subsequent uppercase
        yield 'html5Parser' => ['html5Parser', 'html5_parser'];

        // digit-letter acronym is ambiguous — `3D` is split because `3` behaves like lowercase
        yield 'load3DModel' => ['load3DModel', 'load3_d_model'];

        // adjacent acronyms collapse — no information to split on
        yield 'getHTTPURL' => ['getHTTPURL', 'get_httpurl'];

        // already snake_case stays as-is
        yield 'already_snake' => ['already_snake', 'already_snake'];
        yield 'foo_bar_baz' => ['foo_bar_baz', 'foo_bar_baz'];

        // non-ASCII letters are not recognised as word characters by the regex
        yield 'unicode lowercase preceding uppercase is not a boundary' => ['žlutýString', 'žlutýstring'];
        yield 'ASCII lowercase preceding uppercase still splits around unicode' => ['čauString', 'čau_string'];

        // empty string
        yield 'empty' => ['', ''];
    }

    /**
     * @param class-string $className
     * @param array<string, mixed> $snakeCaseData
     */
    #[DataProvider('provideRoundTripData')]
    public function testRoundTrip(
        string $className,
        array $snakeCaseData,
    ): void
    {
        $provider = $this->createProvider();
        $object = $provider->getInputMapper($className)->map($snakeCaseData);
        self::assertSame($snakeCaseData, $provider->getOutputMapper($className)->map($object));
    }

    /**
     * @return iterable<string, array{class-string, array<string, mixed>}>
     */
    public static function provideRoundTripData(): iterable
    {
        yield 'plain camelCase properties' => [
            CamelCasePropertiesInput::class,
            ['user_id' => 1, 'first_name' => 'John', 'last_name' => 'Doe'],
        ];

        yield 'properties with embedded acronyms' => [
            AcronymCasedInput::class,
            ['http_server' => 'nginx', 'user_id_value' => 7],
        ];
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
