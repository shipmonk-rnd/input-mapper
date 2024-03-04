<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Runtime\Exception;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\DataProvider;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonkTests\InputMapper\InputMapperTestCase;
use stdClass;
use function str_repeat;
use const INF;
use const NAN;

class MappingFailedExceptionTest extends InputMapperTestCase
{

    /**
     * @param list<string|int> $path
     */
    #[DataProvider('provideMessagesData')]
    public function testMessages(MappingFailedException $exception, string $expectedMessage, array $path): void
    {
        self::assertSame($expectedMessage, $exception->getMessage());
        self::assertSame($path, $exception->getPath());
    }

    /**
     * @return iterable<string, array{MappingFailedException, string, list<string|int>}>
     */
    public static function provideMessagesData(): iterable
    {
        yield 'null' => [
            MappingFailedException::incorrectValue(null, ['foo'], 'int'),
            'Failed to map data at path /foo: Expected int, got null',
            ['foo'],
        ];

        yield 'true' => [
            MappingFailedException::incorrectValue(true, ['foo'], 'int'),
            'Failed to map data at path /foo: Expected int, got true',
            ['foo'],
        ];

        yield '123' => [
            MappingFailedException::incorrectValue(123, ['foo'], 'string'),
            'Failed to map data at path /foo: Expected string, got 123',
            ['foo'],
        ];

        yield '1.23' => [
            MappingFailedException::incorrectValue(1.23, ['foo'], 'string'),
            'Failed to map data at path /foo: Expected string, got 1.23',
            ['foo'],
        ];

        yield '1.0' => [
            MappingFailedException::incorrectValue(1.0, ['foo'], 'string'),
            'Failed to map data at path /foo: Expected string, got 1.0',
            ['foo'],
        ];

        yield 'INF' => [
            MappingFailedException::incorrectValue(INF, ['foo'], 'string'),
            'Failed to map data at path /foo: Expected string, got INF',
            ['foo'],
        ];

        yield 'NAN' => [
            MappingFailedException::incorrectValue(NAN, ['foo'], 'string'),
            'Failed to map data at path /foo: Expected string, got NAN',
            ['foo'],
        ];

        yield 'short string' => [
            MappingFailedException::incorrectValue('short string', ['foo'], 'int'),
            'Failed to map data at path /foo: Expected int, got "short string"',
            ['foo'],
        ];

        yield 'long string' => [
            MappingFailedException::incorrectValue(str_repeat('a', 1_000), ['foo'], 'int'),
            'Failed to map data at path /foo: Expected int, got "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa" (truncated)',
            ['foo'],
        ];

        yield 'string with slash' => [
            MappingFailedException::incorrectValue('foo/bar', ['foo'], 'int'),
            'Failed to map data at path /foo: Expected int, got "foo/bar"',
            ['foo'],
        ];

        yield 'string with control characters' => [
            MappingFailedException::incorrectValue("foo\x00bar", ['foo'], 'int'),
            'Failed to map data at path /foo: Expected int, got string',
            ['foo'],
        ];

        yield 'string with invalid UTF-8' => [
            MappingFailedException::incorrectValue("foo\x80bar", ['foo'], 'int'),
            'Failed to map data at path /foo: Expected int, got string',
            ['foo'],
        ];

        yield 'array' => [
            MappingFailedException::incorrectValue([], ['foo'], 'int'),
            'Failed to map data at path /foo: Expected int, got array',
            ['foo'],
        ];

        yield 'date UTC' => [
            MappingFailedException::incorrectValue(new DateTimeImmutable('2023-05-25'), ['foo'], 'int'),
            'Failed to map data at path /foo: Expected int, got 2023-05-25 (UTC)',
            ['foo'],
        ];

        yield 'date Prague' => [
            MappingFailedException::incorrectValue(new DateTimeImmutable('2023-05-25', new DateTimeZone('Europe/Prague')), ['foo'], 'int'),
            'Failed to map data at path /foo: Expected int, got 2023-05-25 (Europe/Prague)',
            ['foo'],
        ];

        yield 'datetime UTC' => [
            MappingFailedException::incorrectValue(new DateTimeImmutable('2023-05-25T12:14:15'), ['foo'], 'int'),
            'Failed to map data at path /foo: Expected int, got 2023-05-25T12:14:15+00:00',
            ['foo'],
        ];

        yield 'datetime Prague' => [
            MappingFailedException::incorrectValue(new DateTimeImmutable('2023-05-25T12:14:15', new DateTimeZone('Europe/Prague')), ['foo'], 'int'),
            'Failed to map data at path /foo: Expected int, got 2023-05-25T12:14:15+02:00',
            ['foo'],
        ];

        yield 'object' => [
            MappingFailedException::incorrectValue(new stdClass(), ['foo'], 'int'),
            'Failed to map data at path /foo: Expected int, got stdClass',
            ['foo'],
        ];

        yield 'nested path' => [
            MappingFailedException::incorrectValue(null, ['foo', 'bar'], 'int'),
            'Failed to map data at path /foo/bar: Expected int, got null',
            ['foo', 'bar'],
        ];
    }

}
