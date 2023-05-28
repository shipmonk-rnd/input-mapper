<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Validator\Object;

use DateTimeImmutable;
use DateTimeZone;
use ShipMonk\InputMapper\Compiler\Validator\Object\AssertDateTimeRange;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonkTests\InputMapper\Compiler\Validator\ValidatorCompilerTestCase;

class AssertDateTimeRangeTest extends ValidatorCompilerTestCase
{

    public function testNoopDateTimeRangeValidator(): void
    {
        $validatorCompiler = new AssertDateTimeRange();
        $validator = $this->compileValidator('NoopDateTimeRangeValidator', $validatorCompiler);

        $validator->map(123);
        $validator->map(null);
        $validator->map([]);
        self::assertTrue(true); // @phpstan-ignore-line always true
    }

    public function testDateTimeRangeValidatorWithInclusiveLowerBound(): void
    {
        $validatorCompiler = new AssertDateTimeRange(gte: '2000-01-05');
        $validator = $this->compileValidator('DateTimeRangeValidatorWithInclusiveLowerBound', $validatorCompiler);

        $validator->map(new DateTimeImmutable('2000-01-05'));
        $validator->map(new DateTimeImmutable('2000-01-06'));
        $validator->map(null);
        $validator->map([]);

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected value greater than or equal to 2000-01-05, got 2000-01-04 (UTC)',
            static fn() => $validator->map(new DateTimeImmutable('2000-01-04')),
        );
    }

    public function testDateTimeRangeValidatorWithExclusiveLowerBound(): void
    {
        $validatorCompiler = new AssertDateTimeRange(gt: '2000-01-05');
        $validator = $this->compileValidator('DateTimeRangeValidatorWithExclusiveLowerBound', $validatorCompiler);

        $validator->map(new DateTimeImmutable('2000-01-06'));
        $validator->map(null);
        $validator->map([]);

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected value greater than 2000-01-05, got 2000-01-05 (UTC)',
            static fn() => $validator->map(new DateTimeImmutable('2000-01-05')),
        );
    }

    public function testDateTimeRangeValidatorWithInclusiveUpperBound(): void
    {
        $validatorCompiler = new AssertDateTimeRange(lte: '2000-01-05');
        $validator = $this->compileValidator('DateTimeRangeValidatorWithInclusiveUpperBound', $validatorCompiler);

        $validator->map(new DateTimeImmutable('2000-01-05'));
        $validator->map(new DateTimeImmutable('2000-01-04'));
        $validator->map(null);
        $validator->map([]);

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected value less than or equal to 2000-01-05, got 2000-01-06 (UTC)',
            static fn() => $validator->map(new DateTimeImmutable('2000-01-06')),
        );
    }

    public function testDateTimeRangeValidatorWithExclusiveUpperBound(): void
    {
        $validatorCompiler = new AssertDateTimeRange(lt: '2000-01-05');
        $validator = $this->compileValidator('DateTimeRangeValidatorWithExclusiveUpperBound', $validatorCompiler);

        $validator->map(new DateTimeImmutable('2000-01-04'));
        $validator->map(null);
        $validator->map([]);

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected value less than 2000-01-05, got 2000-01-05 (UTC)',
            static fn() => $validator->map(new DateTimeImmutable('2000-01-05')),
        );
    }

    public function testDateTimeRangeValidatorWithInclusiveLowerAndUpperBound(): void
    {
        $validatorCompiler = new AssertDateTimeRange(gte: '2000-01-05', lte: '2000-01-10');
        $validator = $this->compileValidator('DateTimeRangeValidatorWithInclusiveLowerAndUpperBound', $validatorCompiler);

        $validator->map(new DateTimeImmutable('2000-01-05'));
        $validator->map(new DateTimeImmutable('2000-01-06'));
        $validator->map(new DateTimeImmutable('2000-01-10'));
        $validator->map(null);
        $validator->map([]);

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected value greater than or equal to 2000-01-05, got 2000-01-04 (UTC)',
            static fn() => $validator->map(new DateTimeImmutable('2000-01-04')),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected value less than or equal to 2000-01-10, got 2000-01-11 (UTC)',
            static fn() => $validator->map(new DateTimeImmutable('2000-01-11')),
        );
    }

    public function testDateTimeRangeValidatorWithBothDateAndTime(): void
    {
        $validatorCompiler = new AssertDateTimeRange(gte: '2000-01-01T00:00:05Z');
        $validator = $this->compileValidator('DateTimeRangeValidatorWithBothDateAndTime', $validatorCompiler);

        $validator->map(new DateTimeImmutable('2000-01-01T00:00:05Z'));
        $validator->map(new DateTimeImmutable('2000-01-01T02:00:05+02:00'));
        $validator->map(new DateTimeImmutable('1999-12-31T19:00:05-05:00'));
        $validator->map(new DateTimeImmutable('2000-01-01T00:00:06Z'));
        $validator->map(null);
        $validator->map([]);

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected value greater than or equal to 2000-01-01T00:00:05Z, got 2000-01-01T00:00:04+00:00',
            static fn() => $validator->map(new DateTimeImmutable('2000-01-01T00:00:04Z')),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected value greater than or equal to 2000-01-01T00:00:05Z, got 2000-01-01T02:00:04+02:00',
            static fn() => $validator->map(new DateTimeImmutable('2000-01-01T02:00:04+02:00')),
        );
    }

    public function testDateTimeRangeValidatorWithRelativeBound(): void
    {
        $validatorCompiler = new AssertDateTimeRange(gte: 'now');
        $validator = $this->compileValidator('DateTimeRangeValidatorWithRelativeBound', $validatorCompiler);

        $validator->map(new DateTimeImmutable('+1 day'));
        $validator->map(new DateTimeImmutable('+1 hour'));
        $validator->map(null);
        $validator->map([]);

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected value greater than or equal to now, got %s',
            static fn() => $validator->map(new DateTimeImmutable('-1 minute')),
        );
    }

    public function testDateTimeRangeValidatorWithTimezone(): void
    {
        $validatorCompiler = new AssertDateTimeRange(gte: '2000-01-05', timezone: 'America/New_York');
        $validator = $this->compileValidator('DateTimeRangeValidatorWithTimezone', $validatorCompiler);

        $validator->map(new DateTimeImmutable('2000-01-05', new DateTimeZone('America/New_York')));
        $validator->map(new DateTimeImmutable('2000-01-06', new DateTimeZone('America/New_York')));
        $validator->map(null);
        $validator->map([]);

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected value greater than or equal to 2000-01-05 (in America/New_York timezone), got 2000-01-04 (America/New_York)',
            static fn() => $validator->map(new DateTimeImmutable('2000-01-04', new DateTimeZone('America/New_York'))),
        );
    }

}
