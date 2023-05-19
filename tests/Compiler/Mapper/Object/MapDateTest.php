<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Object;

use DateTimeImmutable;
use ShipMonk\InputMapper\Compiler\Mapper\Object\MapDate;
use ShipMonk\InputMapper\Compiler\Mapper\Object\MapDateTimeImmutable;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonkTests\InputMapper\Compiler\Mapper\MapperCompilerTestCase;

class MapDateTest extends MapperCompilerTestCase
{

    public function testCompile(): void
    {
        $mapperCompiler = new MapDate();

        /** @var Mapper<DateTimeImmutable> $mapper */
        $mapper = $this->compileMapper('DateStandalone', $mapperCompiler);

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

        /** @var Mapper<DateTimeImmutable> $mapper */
        $mapper = $this->compileMapper('DateStandaloneWithTimeZone', $mapperCompiler);

        self::assertSame('1985-04-12T00:00:00.000+02:00', $mapper->map('1985-04-12')->format(DateTimeImmutable::RFC3339_EXTENDED));
    }

}
