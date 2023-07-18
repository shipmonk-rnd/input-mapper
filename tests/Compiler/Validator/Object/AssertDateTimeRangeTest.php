<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Validator\Object;

use DateTimeImmutable;
use DateTimeInterface;
use ShipMonk\InputMapper\Compiler\Mapper\Object\MapDate;
use ShipMonk\InputMapper\Compiler\Mapper\Object\MapDateTimeImmutable;
use ShipMonk\InputMapper\Compiler\Validator\Object\AssertDateTimeRange;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonkTests\InputMapper\Compiler\Validator\ValidatorCompilerTestCase;

class AssertDateTimeRangeTest extends ValidatorCompilerTestCase
{

    public function testNoopDateTimeRangeValidator(): void
    {
        $mapperCompiler = new MapDate();
        $validatorCompiler = new AssertDateTimeRange();
        $validator = $this->compileValidator('NoopDateTimeRangeValidator', $mapperCompiler, $validatorCompiler);

        $validator->map('2000-01-05');
        self::assertTrue(true); // @phpstan-ignore-line always true
    }

    public function testDateTimeRangeValidatorWithInclusiveLowerBound(): void
    {
        $mapperCompiler = new MapDate();
        $validatorCompiler = new AssertDateTimeRange(gte: '2000-01-05');
        $validator = $this->compileValidator('DateTimeRangeValidatorWithInclusiveLowerBound', $mapperCompiler, $validatorCompiler);

        $validator->map('2000-01-05');
        $validator->map('2000-01-06');

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected value greater than or equal to 2000-01-05, got 2000-01-04 (UTC)',
            static fn() => $validator->map('2000-01-04'),
        );
    }

    public function testDateTimeRangeValidatorWithExclusiveLowerBound(): void
    {
        $mapperCompiler = new MapDate();
        $validatorCompiler = new AssertDateTimeRange(gt: '2000-01-05');
        $validator = $this->compileValidator('DateTimeRangeValidatorWithExclusiveLowerBound', $mapperCompiler, $validatorCompiler);

        $validator->map('2000-01-06');

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected value greater than 2000-01-05, got 2000-01-05 (UTC)',
            static fn() => $validator->map('2000-01-05'),
        );
    }

    public function testDateTimeRangeValidatorWithInclusiveUpperBound(): void
    {
        $mapperCompiler = new MapDate();
        $validatorCompiler = new AssertDateTimeRange(lte: '2000-01-05');
        $validator = $this->compileValidator('DateTimeRangeValidatorWithInclusiveUpperBound', $mapperCompiler, $validatorCompiler);

        $validator->map('2000-01-05');
        $validator->map('2000-01-04');

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected value less than or equal to 2000-01-05, got 2000-01-06 (UTC)',
            static fn() => $validator->map('2000-01-06'),
        );
    }

    public function testDateTimeRangeValidatorWithExclusiveUpperBound(): void
    {
        $mapperCompiler = new MapDate();
        $validatorCompiler = new AssertDateTimeRange(lt: '2000-01-05');
        $validator = $this->compileValidator('DateTimeRangeValidatorWithExclusiveUpperBound', $mapperCompiler, $validatorCompiler);

        $validator->map('2000-01-04');

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected value less than 2000-01-05, got 2000-01-05 (UTC)',
            static fn() => $validator->map('2000-01-05'),
        );
    }

    public function testDateTimeRangeValidatorWithInclusiveLowerAndUpperBound(): void
    {
        $mapperCompiler = new MapDate();
        $validatorCompiler = new AssertDateTimeRange(gte: '2000-01-05', lte: '2000-01-10');
        $validator = $this->compileValidator('DateTimeRangeValidatorWithInclusiveLowerAndUpperBound', $mapperCompiler, $validatorCompiler);

        $validator->map('2000-01-05');
        $validator->map('2000-01-06');
        $validator->map('2000-01-10');

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected value greater than or equal to 2000-01-05, got 2000-01-04 (UTC)',
            static fn() => $validator->map('2000-01-04'),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected value less than or equal to 2000-01-10, got 2000-01-11 (UTC)',
            static fn() => $validator->map('2000-01-11'),
        );
    }

    public function testDateTimeRangeValidatorWithBothDateAndTime(): void
    {
        $mapperCompiler = new MapDateTimeImmutable(format: DateTimeInterface::RFC3339);
        $validatorCompiler = new AssertDateTimeRange(gte: '2000-01-01T00:00:05Z');
        $validator = $this->compileValidator('DateTimeRangeValidatorWithBothDateAndTime', $mapperCompiler, $validatorCompiler);

        $validator->map('2000-01-01T00:00:05Z');
        $validator->map('2000-01-01T02:00:05+02:00');
        $validator->map('1999-12-31T19:00:05-05:00');
        $validator->map('2000-01-01T00:00:06Z');

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected value greater than or equal to 2000-01-01T00:00:05Z, got 2000-01-01T00:00:04+00:00',
            static fn() => $validator->map('2000-01-01T00:00:04Z'),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected value greater than or equal to 2000-01-01T00:00:05Z, got 2000-01-01T02:00:04+02:00',
            static fn() => $validator->map('2000-01-01T02:00:04+02:00'),
        );
    }

    public function testDateTimeRangeValidatorWithRelativeBound(): void
    {
        $mapperCompiler = new MapDateTimeImmutable(format: DateTimeInterface::RFC3339);
        $validatorCompiler = new AssertDateTimeRange(gte: 'now');
        $validator = $this->compileValidator('DateTimeRangeValidatorWithRelativeBound', $mapperCompiler, $validatorCompiler);

        $validator->map((new DateTimeImmutable('+1 day'))->format(DateTimeInterface::RFC3339));
        $validator->map((new DateTimeImmutable('+1 hour'))->format(DateTimeInterface::RFC3339));

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected value greater than or equal to now, got %s',
            static fn() => $validator->map((new DateTimeImmutable('-1 minute'))->format(DateTimeInterface::RFC3339)),
        );
    }

    public function testDateTimeRangeValidatorWithTimezone(): void
    {
        $mapperCompiler = new MapDateTimeImmutable(format: '!Y-m-d', defaultTimezone: 'America/New_York');
        $validatorCompiler = new AssertDateTimeRange(gte: '2000-01-05', timezone: 'America/New_York');
        $validator = $this->compileValidator('DateTimeRangeValidatorWithTimezone', $mapperCompiler, $validatorCompiler);

        $validator->map('2000-01-05');
        $validator->map('2000-01-06');

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected value greater than or equal to 2000-01-05 (in America/New_York timezone), got 2000-01-04 (America/New_York)',
            static fn() => $validator->map('2000-01-04'),
        );
    }

}
