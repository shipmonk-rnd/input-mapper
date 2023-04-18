<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Runtime;

use DateTimeImmutable;
use ShipMonk\InputMapper\Runtime\MappingFailedException;
use ShipMonkTests\InputMapper\InputMapperTestCase;
use function str_repeat;
use const INF;
use const NAN;

class MappingFailedExceptionTest extends InputMapperTestCase
{

    /**
     * @dataProvider provideMessagesData
     */
    public function testMessages(MappingFailedException $exception, string $expectedMessage): void
    {
        self::assertSame($expectedMessage, $exception->getMessage());
    }

    /**
     * @return iterable<string, array{MappingFailedException, string}>
     */
    public function provideMessagesData(): iterable
    {
        yield 'null' => [
            MappingFailedException::incorrectValue(null, ['foo'], 'int'),
            'Failed to map data at path /foo: Expected int, got null',
        ];

        yield 'true' => [
            MappingFailedException::incorrectValue(true, ['foo'], 'int'),
            'Failed to map data at path /foo: Expected int, got true',
        ];

        yield '123' => [
            MappingFailedException::incorrectValue(123, ['foo'], 'string'),
            'Failed to map data at path /foo: Expected string, got 123',
        ];

        yield '1.23' => [
            MappingFailedException::incorrectValue(1.23, ['foo'], 'string'),
            'Failed to map data at path /foo: Expected string, got 1.23',
        ];

        yield '1.0' => [
            MappingFailedException::incorrectValue(1.0, ['foo'], 'string'),
            'Failed to map data at path /foo: Expected string, got 1.0',
        ];

        yield 'INF' => [
            MappingFailedException::incorrectValue(INF, ['foo'], 'string'),
            'Failed to map data at path /foo: Expected string, got INF',
        ];

        yield 'NAN' => [
            MappingFailedException::incorrectValue(NAN, ['foo'], 'string'),
            'Failed to map data at path /foo: Expected string, got NAN',
        ];

        yield 'short string' => [
            MappingFailedException::incorrectValue('short string', ['foo'], 'int'),
            'Failed to map data at path /foo: Expected int, got "short string"',
        ];

        yield 'long string' => [
            MappingFailedException::incorrectValue(str_repeat('a', 1_000), ['foo'], 'int'),
            'Failed to map data at path /foo: Expected int, got "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa" (truncated)',
        ];

        yield 'string with control characters' => [
            MappingFailedException::incorrectValue("foo\x00bar", ['foo'], 'int'),
            'Failed to map data at path /foo: Expected int, got string',
        ];

        yield 'string with invalid UTF-8' => [
            MappingFailedException::incorrectValue("foo\x80bar", ['foo'], 'int'),
            'Failed to map data at path /foo: Expected int, got string',
        ];

        yield 'array' => [
            MappingFailedException::incorrectValue([], ['foo'], 'int'),
            'Failed to map data at path /foo: Expected int, got array',
        ];

        yield 'object' => [
            MappingFailedException::incorrectValue(new DateTimeImmutable('now'), ['foo'], 'int'),
            'Failed to map data at path /foo: Expected int, got DateTimeImmutable',
        ];
    }

}
