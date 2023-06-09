<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Object;

use DateTimeImmutable;
use ShipMonk\InputMapper\Compiler\Mapper\Object\MapDateTimeImmutable;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonkTests\InputMapper\Compiler\Mapper\MapperCompilerTestCase;

class MapDateTimeImmutableTest extends MapperCompilerTestCase
{

    public function testCompile(): void
    {
        $mapperCompiler = new MapDateTimeImmutable();

        /** @var Mapper<DateTimeImmutable> $mapper */
        $mapper = $this->compileMapper('DateTimeImmutable', $mapperCompiler);

        self::assertSame('1985-04-12T23:20:50.000+00:00', $mapper->map('1985-04-12T23:20:50Z')->format(DateTimeImmutable::RFC3339_EXTENDED));
        self::assertSame('1985-04-12T23:20:50.123+00:00', $mapper->map('1985-04-12T23:20:50.123Z')->format(DateTimeImmutable::RFC3339_EXTENDED));
        self::assertSame('1937-01-01T12:00:27.000+00:20', $mapper->map('1937-01-01T12:00:27+00:20')->format(DateTimeImmutable::RFC3339_EXTENDED));

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected string, got 123',
            static fn() => $mapper->map(123),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected string, got null',
            static fn() => $mapper->map(null),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected date-time string in RFC 3339 format, got "abc"',
            static fn() => $mapper->map('abc'),
        );
    }

    public function testCompileWithTimeZone(): void
    {
        $mapperCompiler = new MapDateTimeImmutable(defaultTimezone: 'Europe/Prague', targetTimezone: 'Europe/Prague');

        /** @var Mapper<DateTimeImmutable> $mapper */
        $mapper = $this->compileMapper('DateTimeImmutableWithTimeZone', $mapperCompiler);

        self::assertSame('1985-04-13T01:20:50.000+02:00', $mapper->map('1985-04-12T23:20:50Z')->format(DateTimeImmutable::RFC3339_EXTENDED));
        self::assertSame('1985-04-13T01:20:50.123+02:00', $mapper->map('1985-04-12T23:20:50.123Z')->format(DateTimeImmutable::RFC3339_EXTENDED));
        self::assertSame('1937-01-01T12:40:27.000+01:00', $mapper->map('1937-01-01T12:00:27+00:20')->format(DateTimeImmutable::RFC3339_EXTENDED));
    }

    public function testCompileWithTargetTimeZone(): void
    {
        $mapperCompiler = new MapDateTimeImmutable(targetTimezone: 'Europe/Prague');

        /** @var Mapper<DateTimeImmutable> $mapper */
        $mapper = $this->compileMapper('DateTimeImmutableWithTargetTimeZone', $mapperCompiler);

        self::assertSame('1985-04-13T01:20:50.000+02:00', $mapper->map('1985-04-12T23:20:50Z')->format(DateTimeImmutable::RFC3339_EXTENDED));
        self::assertSame('1985-04-13T01:20:50.123+02:00', $mapper->map('1985-04-12T23:20:50.123Z')->format(DateTimeImmutable::RFC3339_EXTENDED));
        self::assertSame('1937-01-01T12:40:27.000+01:00', $mapper->map('1937-01-01T12:00:27+00:20')->format(DateTimeImmutable::RFC3339_EXTENDED));
    }

    public function testCompileWithCustomFormat(): void
    {
        $mapperCompiler = new MapDateTimeImmutable('!Y-m-d', 'date string in Y-m-d format');

        /** @var Mapper<DateTimeImmutable> $mapper */
        $mapper = $this->compileMapper('Date', $mapperCompiler);

        self::assertSame('1985-04-12T00:00:00.000+00:00', $mapper->map('1985-04-12')->format(DateTimeImmutable::RFC3339_EXTENDED));

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected string, got 123',
            static fn() => $mapper->map(123),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected string, got null',
            static fn() => $mapper->map(null),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected date string in Y-m-d format, got "1985-04-12T23:20:50Z"',
            static fn() => $mapper->map('1985-04-12T23:20:50Z'),
        );
    }

    public function testCompileWithCustomFormatAndTimeZone(): void
    {
        $mapperCompiler = new MapDateTimeImmutable(
            format: ['!Y-m-d'],
            formatDescription: 'date string in Y-m-d format',
            defaultTimezone: 'Europe/Prague',
            targetTimezone: 'Europe/Prague',
        );

        /** @var Mapper<DateTimeImmutable> $mapper */
        $mapper = $this->compileMapper('DateWithTimeZone', $mapperCompiler);

        self::assertSame('1985-04-12T00:00:00.000+02:00', $mapper->map('1985-04-12')->format(DateTimeImmutable::RFC3339_EXTENDED));
    }

    public function testCompileWithCustomFormatAndDefaultTimeZone(): void
    {
        $mapperCompiler = new MapDateTimeImmutable(
            format: ['!Y-m-d'],
            formatDescription: 'date string in Y-m-d format',
            defaultTimezone: 'America/New_York',
        );

        /** @var Mapper<DateTimeImmutable> $mapper */
        $mapper = $this->compileMapper('DateWithDefaultTimeZone', $mapperCompiler);

        self::assertSame('1985-04-12T00:00:00.000-05:00', $mapper->map('1985-04-12')->format(DateTimeImmutable::RFC3339_EXTENDED));
    }

    public function testCompileWithCustomFormatAndTargetTimeZone(): void
    {
        $mapperCompiler = new MapDateTimeImmutable(
            format: ['!Y-m-d'],
            formatDescription: 'date string in Y-m-d format',
            targetTimezone: 'America/New_York',
        );

        /** @var Mapper<DateTimeImmutable> $mapper */
        $mapper = $this->compileMapper('DateWithTargetTimeZone', $mapperCompiler);

        self::assertSame('1985-04-11T19:00:00.000-05:00', $mapper->map('1985-04-12')->format(DateTimeImmutable::RFC3339_EXTENDED));
    }

    public function testCompileWithCustomFormatAndDistinctDefaultAndTargetTimeZone(): void
    {
        $mapperCompiler = new MapDateTimeImmutable(
            format: ['!Y-m-d'],
            formatDescription: 'date string in Y-m-d format',
            defaultTimezone: 'Europe/Prague',
            targetTimezone: 'America/New_York',
        );

        /** @var Mapper<DateTimeImmutable> $mapper */
        $mapper = $this->compileMapper('DateWithDistinctDefaultAndTargetTimeZone', $mapperCompiler);

        self::assertSame('1985-04-11T17:00:00.000-05:00', $mapper->map('1985-04-12')->format(DateTimeImmutable::RFC3339_EXTENDED));
    }

}
