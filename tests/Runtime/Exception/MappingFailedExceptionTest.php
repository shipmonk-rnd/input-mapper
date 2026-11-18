<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Runtime\Exception;

use DateTimeImmutable;
use DateTimeZone;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapperTests\InputMapperTestCase;
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
    public function testMessages(
        MappingFailedException $exception,
        string $expectedMessage,
        array $path,
    ): void
    {
        self::assertSame($expectedMessage, $exception->getMessage());
        self::assertSame($path, $exception->getPath());
    }

    /**
     * @param list<string|int> $path
     */
    #[DataProvider('provideRedactedMessagesData')]
    public function testRedactedMessages(
        MappingFailedException $exception,
        string $expectedMessage,
        array $path,
    ): void
    {
        $redacted = MappingFailedException::redact($exception);
        self::assertSame($expectedMessage, $redacted->getMessage());
        self::assertSame($path, $redacted->getPath());
    }

    /**
     * @return iterable<string, array{MappingFailedException, string, list<string|int>}>
     */
    public static function provideRedactedMessagesData(): iterable
    {
        yield 'incorrect type' => [
            MappingFailedException::incorrectType('hunter2', ['foo'], 'int'),
            'Failed to map data at path /foo: Expected int, got string (redacted)',
            ['foo'],
        ];

        yield 'incorrect value' => [
            MappingFailedException::incorrectValue(123, ['foo'], 'positive int'),
            'Failed to map data at path /foo: Expected positive int, got int (redacted)',
            ['foo'],
        ];

        yield 'duplicate value' => [
            MappingFailedException::duplicateValue('hunter2', ['foo'], 'unique string'),
            'Failed to map data at path /foo: Expected unique string, got string (redacted) multiple times',
            ['foo'],
        ];

        yield 'missing key' => [
            MappingFailedException::missingKey(['foo'], 'bar'),
            'Failed to map data at path /foo: Missing required key "bar"',
            ['foo'],
        ];

        yield 'extra keys' => [
            MappingFailedException::extraKeys(['foo'], ['bar']),
            'Failed to map data at path /foo: Unrecognized key "bar"',
            ['foo'],
        ];

        yield 'object' => [
            MappingFailedException::incorrectValue(new stdClass(), ['foo'], 'int'),
            'Failed to map data at path /foo: Expected int, got stdClass (redacted)',
            ['foo'],
        ];
    }

    public function testRedactedDropsUnrelatedPreviousException(): void
    {
        $exception = MappingFailedException::incorrectValue('hunter2', ['foo'], 'int', new LogicException('hunter2'));
        self::assertNull(MappingFailedException::redact($exception)->getPrevious());
    }

    public function testRedactedRedactsPreviousMappingFailure(): void
    {
        $previous = MappingFailedException::incorrectType('hunter2', ['foo', 'bar'], 'int');
        $exception = MappingFailedException::incorrectValue('hunter2', ['foo'], 'int', $previous);
        $redactedPrevious = MappingFailedException::redact($exception)->getPrevious();

        self::assertInstanceOf(MappingFailedException::class, $redactedPrevious);
        self::assertSame('Failed to map data at path /foo/bar: Expected int, got string (redacted)', $redactedPrevious->getMessage());
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
            'Failed to map data at path /foo: Expected int, got empty list',
            ['foo'],
        ];

        yield 'list' => [
            MappingFailedException::incorrectValue(['a', 'b', 'c'], ['foo'], 'int'),
            'Failed to map data at path /foo: Expected int, got list with 3 items',
            ['foo'],
        ];

        yield 'list with single item' => [
            MappingFailedException::incorrectValue(['a'], ['foo'], 'int'),
            'Failed to map data at path /foo: Expected int, got list with 1 item',
            ['foo'],
        ];

        yield 'non-list array' => [
            MappingFailedException::incorrectValue(['a' => 1, 'b' => 2], ['foo'], 'int'),
            'Failed to map data at path /foo: Expected int, got array with 2 items',
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
