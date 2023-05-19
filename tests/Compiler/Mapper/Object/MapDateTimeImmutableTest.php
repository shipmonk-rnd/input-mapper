<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Object;

use DateTimeImmutable;
use ShipMonk\InputMapper\Compiler\Mapper\Object\MapDateTimeImmutable;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonkTests\InputMapper\Compiler\Mapper\MapperCompilerTestCase;

class MapDateTimeImmutableTest extends MapperCompilerTestCase
{

    public function testCompile(): void
    {
        $mapperCompiler = new MapDateTimeImmutable();
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
        $mapperCompiler = new MapDateTimeImmutable(timezone: 'Europe/Prague');
        $mapper = $this->compileMapper('DateTimeImmutableWithTimeZone', $mapperCompiler);

        self::assertSame('1985-04-13T01:20:50.000+02:00', $mapper->map('1985-04-12T23:20:50Z')->format(DateTimeImmutable::RFC3339_EXTENDED));
        self::assertSame('1985-04-13T01:20:50.123+02:00', $mapper->map('1985-04-12T23:20:50.123Z')->format(DateTimeImmutable::RFC3339_EXTENDED));
        self::assertSame('1937-01-01T12:40:27.000+01:00', $mapper->map('1937-01-01T12:00:27+00:20')->format(DateTimeImmutable::RFC3339_EXTENDED));
    }

    public function testCompileWithCustomFormat(): void
    {
        $mapperCompiler = new MapDateTimeImmutable('!Y-m-d', 'date string in Y-m-d format');
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
        $mapperCompiler = new MapDateTimeImmutable(['!Y-m-d'], 'date string in Y-m-d format', 'Europe/Prague');
        $mapper = $this->compileMapper('DateWithTimeZone', $mapperCompiler);

        self::assertSame('1985-04-12T00:00:00.000+02:00', $mapper->map('1985-04-12')->format(DateTimeImmutable::RFC3339_EXTENDED));
    }

}
