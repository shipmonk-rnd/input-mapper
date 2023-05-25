<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Validator\Object;

use DateTimeImmutable;
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

}
