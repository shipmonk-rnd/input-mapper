<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Object;

use DateTimeImmutable;
use DateTimeInterface;
use ShipMonk\InputMapper\Compiler\Mapper\Output\DateTimeImmutableOutputMapperCompiler;
use ShipMonk\InputMapperTests\Compiler\Mapper\OutputMapperCompilerTestCase;

class DateTimeImmutableOutputMapperCompilerTest extends OutputMapperCompilerTestCase
{

    public function testCompileWithDefaultFormat(): void
    {
        $mapperCompiler = new DateTimeImmutableOutputMapperCompiler();
        $mapper = $this->compileOutputMapper('DateTimeImmutableDefault', $mapperCompiler);

        $dateTime = new DateTimeImmutable('2024-01-15T10:30:00+00:00');
        self::assertSame('2024-01-15T10:30:00+00:00', $mapper->map($dateTime));
    }

    public function testCompileWithCustomFormat(): void
    {
        $mapperCompiler = new DateTimeImmutableOutputMapperCompiler('Y-m-d');
        $mapper = $this->compileOutputMapper('DateTimeImmutableCustomFormat', $mapperCompiler);

        $dateTime = new DateTimeImmutable('2024-01-15T10:30:00+00:00');
        self::assertSame('2024-01-15', $mapper->map($dateTime));
    }

    public function testCompileWithMultipleFormats(): void
    {
        $mapperCompiler = new DateTimeImmutableOutputMapperCompiler(DateTimeInterface::RFC3339);
        $mapper = $this->compileOutputMapper('DateTimeImmutableMultiFormat', $mapperCompiler);

        $dateTime = new DateTimeImmutable('2024-01-15T10:30:00+00:00');
        self::assertSame('2024-01-15T10:30:00+00:00', $mapper->map($dateTime));
    }

    public function testCompileWithTargetTimezone(): void
    {
        $mapperCompiler = new DateTimeImmutableOutputMapperCompiler(DateTimeInterface::RFC3339, 'Europe/Prague');
        $mapper = $this->compileOutputMapper('DateTimeImmutableWithTimezone', $mapperCompiler);

        $dateTime = new DateTimeImmutable('2024-01-15T10:30:00+00:00');
        self::assertSame('2024-01-15T11:30:00+01:00', $mapper->map($dateTime));
    }

}
