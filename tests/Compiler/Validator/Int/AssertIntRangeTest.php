<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Validator\Int;

use ShipMonk\InputMapper\Compiler\Validator\Int\AssertIntRange;
use ShipMonk\InputMapper\Runtime\MappingFailedException;
use ShipMonkTests\InputMapper\Compiler\Validator\ValidatorCompilerTestCase;

class AssertIntRangeTest extends ValidatorCompilerTestCase
{

    public function testNoopIntRangeValidator(): void
    {
        $validatorCompiler = new AssertIntRange();
        $validator = $this->compileValidator('NoopIntRangeValidator', $validatorCompiler);

        $validator->map(123);
        $validator->map(null);
        $validator->map([]);
        self::assertTrue(true); // @phpstan-ignore-line always true
    }

    public function testIntRangeValidatorWithInclusiveLowerBound(): void
    {
        $validatorCompiler = new AssertIntRange(gte: 5);
        $validator = $this->compileValidator('IntRangeValidatorWithInclusiveLowerBound', $validatorCompiler);

        $validator->map(5);
        $validator->map(6);
        $validator->map(null);
        $validator->map([]);

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected value greater than or equal to 5, got 4',
            static fn() => $validator->map(4),
        );
    }

    public function testIntRangeValidatorWithExclusiveLowerBound(): void
    {
        $validatorCompiler = new AssertIntRange(gt: 5);
        $validator = $this->compileValidator('IntRangeValidatorWithExclusiveLowerBound', $validatorCompiler);

        $validator->map(6);
        $validator->map(null);
        $validator->map([]);

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected value greater than 5, got 5',
            static fn() => $validator->map(5),
        );
    }

    public function testIntRangeValidatorWithInclusiveUpperBound(): void
    {
        $validatorCompiler = new AssertIntRange(lte: 5);
        $validator = $this->compileValidator('IntRangeValidatorWithInclusiveUpperBound', $validatorCompiler);

        $validator->map(5);
        $validator->map(4);
        $validator->map(null);
        $validator->map([]);

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected value less than or equal to 5, got 6',
            static fn() => $validator->map(6),
        );
    }

    public function testIntRangeValidatorWithExclusiveUpperBound(): void
    {
        $validatorCompiler = new AssertIntRange(lt: 5);
        $validator = $this->compileValidator('IntRangeValidatorWithExclusiveUpperBound', $validatorCompiler);

        $validator->map(4);
        $validator->map(null);
        $validator->map([]);

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected value less than 5, got 5',
            static fn() => $validator->map(5),
        );
    }

    public function testIntRangeValidatorWithInclusiveLowerAndUpperBound(): void
    {
        $validatorCompiler = new AssertIntRange(gte: 5, lte: 10);
        $validator = $this->compileValidator('IntRangeValidatorWithInclusiveLowerAndUpperBound', $validatorCompiler);

        $validator->map(5);
        $validator->map(6);
        $validator->map(10);
        $validator->map(null);
        $validator->map([]);

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected value greater than or equal to 5, got 4',
            static fn() => $validator->map(4),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected value less than or equal to 10, got 11',
            static fn() => $validator->map(11),
        );
    }

}
