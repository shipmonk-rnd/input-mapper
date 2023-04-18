<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Object;

use DateTimeImmutable;
use ShipMonk\InputMapper\Compiler\Mapper\Object\MapDateTimeImmutable;
use ShipMonk\InputMapper\Runtime\MappingFailedException;
use ShipMonkTests\InputMapper\Compiler\Mapper\MapperCompilerTestCase;

class MapDateTimeImmutableTest extends MapperCompilerTestCase
{

    public function testCompile(): void
    {
        $mapperCompiler = new MapDateTimeImmutable();
        $mapper = $this->compileMapper('DateTimeImmutable', $mapperCompiler);

        self::assertEquals(new DateTimeImmutable('1985-04-12T23:20:50Z'), $mapper->map('1985-04-12T23:20:50Z'));
        self::assertEquals(new DateTimeImmutable('1937-01-01T12:00:27+00:20'), $mapper->map('1937-01-01T12:00:27+00:20'));

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

}
