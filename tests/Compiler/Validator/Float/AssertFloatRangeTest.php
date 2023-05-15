<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Validator\Float;

use ShipMonk\InputMapper\Compiler\Validator\Float\AssertFloatRange;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonkTests\InputMapper\Compiler\Validator\ValidatorCompilerTestCase;

class AssertFloatRangeTest extends ValidatorCompilerTestCase
{

    public function testNoopFloatRangeValidator(): void
    {
        $validatorCompiler = new AssertFloatRange();
        $validator = $this->compileValidator('NoopFloatRangeValidator', $validatorCompiler);

        $validator->map(123);
        $validator->map(1.2);
        $validator->map(null);
        $validator->map([]);
        self::assertTrue(true); // @phpstan-ignore-line always true
    }

    public function testFloatRangeValidatorWithInclusiveLowerBound(): void
    {
        $validatorCompiler = new AssertFloatRange(gte: 5.0);
        $validator = $this->compileValidator('FloatRangeValidatorWithInclusiveLowerBound', $validatorCompiler);

        $validator->map(5.0);
        $validator->map(6.0);
        $validator->map(null);
        $validator->map([]);

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected value greater than or equal to 5, got 4.0',
            static fn() => $validator->map(4.0),
        );
    }

    public function testFloatRangeValidatorWithExclusiveLowerBound(): void
    {
        $validatorCompiler = new AssertFloatRange(gt: 5.0);
        $validator = $this->compileValidator('FloatRangeValidatorWithExclusiveLowerBound', $validatorCompiler);

        $validator->map(6.0);
        $validator->map(null);
        $validator->map([]);

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected value greater than 5, got 5.0',
            static fn() => $validator->map(5.0),
        );
    }

    public function testFloatRangeValidatorWithInclusiveUpperBound(): void
    {
        $validatorCompiler = new AssertFloatRange(lte: 5.0);
        $validator = $this->compileValidator('FloatRangeValidatorWithInclusiveUpperBound', $validatorCompiler);

        $validator->map(5.0);
        $validator->map(4.0);
        $validator->map(null);
        $validator->map([]);

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected value less than or equal to 5, got 6.0',
            static fn() => $validator->map(6.0),
        );
    }

    public function testFloatRangeValidatorWithExclusiveUpperBound(): void
    {
        $validatorCompiler = new AssertFloatRange(lt: 5.0);
        $validator = $this->compileValidator('FloatRangeValidatorWithExclusiveUpperBound', $validatorCompiler);

        $validator->map(4.0);
        $validator->map(null);
        $validator->map([]);

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected value less than 5, got 5.0',
            static fn() => $validator->map(5.0),
        );
    }

    public function testFloatRangeValidatorWithInclusiveLowerAndUpperBound(): void
    {
        $validatorCompiler = new AssertFloatRange(gte: 5.0, lte: 10.0);
        $validator = $this->compileValidator('FloatRangeValidatorWithInclusiveLowerAndUpperBound', $validatorCompiler);

        $validator->map(5.0);
        $validator->map(6.0);
        $validator->map(10.0);
        $validator->map(null);
        $validator->map([]);

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected value greater than or equal to 5, got 4.0',
            static fn() => $validator->map(4.0),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected value less than or equal to 10, got 11.0',
            static fn() => $validator->map(11.0),
        );
    }

}
