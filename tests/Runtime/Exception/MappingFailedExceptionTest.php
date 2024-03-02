<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Runtime\Exception;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\DataProvider;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\MapperContext;
use ShipMonkTests\InputMapper\InputMapperTestCase;
use stdClass;
use function str_repeat;
use const INF;
use const NAN;

class MappingFailedExceptionTest extends InputMapperTestCase
{

    #[DataProvider('provideMessagesData')]
    public function testMessages(MappingFailedException $exception, string $expectedMessage): void
    {
        self::assertSame($expectedMessage, $exception->getMessage());
    }

    /**
     * @return iterable<string, array{MappingFailedException, string}>
     */
    public static function provideMessagesData(): iterable
    {
        $context = new MapperContext(null, 'foo');

        yield 'null' => [
            MappingFailedException::incorrectValue(null, $context, 'int'),
            'Failed to map data at path /foo: Expected int, got null',
        ];

        yield 'true' => [
            MappingFailedException::incorrectValue(true, $context, 'int'),
            'Failed to map data at path /foo: Expected int, got true',
        ];

        yield '123' => [
            MappingFailedException::incorrectValue(123, $context, 'string'),
            'Failed to map data at path /foo: Expected string, got 123',
        ];

        yield '1.23' => [
            MappingFailedException::incorrectValue(1.23, $context, 'string'),
            'Failed to map data at path /foo: Expected string, got 1.23',
        ];

        yield '1.0' => [
            MappingFailedException::incorrectValue(1.0, $context, 'string'),
            'Failed to map data at path /foo: Expected string, got 1.0',
        ];

        yield 'INF' => [
            MappingFailedException::incorrectValue(INF, $context, 'string'),
            'Failed to map data at path /foo: Expected string, got INF',
        ];

        yield 'NAN' => [
            MappingFailedException::incorrectValue(NAN, $context, 'string'),
            'Failed to map data at path /foo: Expected string, got NAN',
        ];

        yield 'short string' => [
            MappingFailedException::incorrectValue('short string', $context, 'int'),
            'Failed to map data at path /foo: Expected int, got "short string"',
        ];

        yield 'long string' => [
            MappingFailedException::incorrectValue(str_repeat('a', 1_000), $context, 'int'),
            'Failed to map data at path /foo: Expected int, got "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa" (truncated)',
        ];

        yield 'string with slash' => [
            MappingFailedException::incorrectValue('foo/bar', $context, 'int'),
            'Failed to map data at path /foo: Expected int, got "foo/bar"',
        ];

        yield 'string with control characters' => [
            MappingFailedException::incorrectValue("foo\x00bar", $context, 'int'),
            'Failed to map data at path /foo: Expected int, got string',
        ];

        yield 'string with invalid UTF-8' => [
            MappingFailedException::incorrectValue("foo\x80bar", $context, 'int'),
            'Failed to map data at path /foo: Expected int, got string',
        ];

        yield 'array' => [
            MappingFailedException::incorrectValue([], $context, 'int'),
            'Failed to map data at path /foo: Expected int, got array',
        ];

        yield 'date UTC' => [
            MappingFailedException::incorrectValue(new DateTimeImmutable('2023-05-25'), $context, 'int'),
            'Failed to map data at path /foo: Expected int, got 2023-05-25 (UTC)',
        ];

        yield 'date Prague' => [
            MappingFailedException::incorrectValue(new DateTimeImmutable('2023-05-25', new DateTimeZone('Europe/Prague')), $context, 'int'),
            'Failed to map data at path /foo: Expected int, got 2023-05-25 (Europe/Prague)',
        ];

        yield 'datetime UTC' => [
            MappingFailedException::incorrectValue(new DateTimeImmutable('2023-05-25T12:14:15'), $context, 'int'),
            'Failed to map data at path /foo: Expected int, got 2023-05-25T12:14:15+00:00',
        ];

        yield 'datetime Prague' => [
            MappingFailedException::incorrectValue(new DateTimeImmutable('2023-05-25T12:14:15', new DateTimeZone('Europe/Prague')), $context, 'int'),
            'Failed to map data at path /foo: Expected int, got 2023-05-25T12:14:15+02:00',
        ];

        yield 'object' => [
            MappingFailedException::incorrectValue(new stdClass(), $context, 'int'),
            'Failed to map data at path /foo: Expected int, got stdClass',
        ];
    }

}
