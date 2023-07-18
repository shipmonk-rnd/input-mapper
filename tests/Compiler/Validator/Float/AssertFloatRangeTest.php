<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Validator\Float;

use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapFloat;
use ShipMonk\InputMapper\Compiler\Validator\Float\AssertFloatRange;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonkTests\InputMapper\Compiler\Validator\ValidatorCompilerTestCase;

class AssertFloatRangeTest extends ValidatorCompilerTestCase
{

    public function testNoopFloatRangeValidator(): void
    {
        $mapperCompiler = new MapFloat();
        $validatorCompiler = new AssertFloatRange();
        $validator = $this->compileValidator('NoopFloatRangeValidator', $mapperCompiler, $validatorCompiler);

        $validator->map(123);
        $validator->map(1.2);
        self::assertTrue(true); // @phpstan-ignore-line always true
    }

    public function testFloatRangeValidatorWithInclusiveLowerBound(): void
    {
        $mapperCompiler = new MapFloat();
        $validatorCompiler = new AssertFloatRange(gte: 5.0);
        $validator = $this->compileValidator('FloatRangeValidatorWithInclusiveLowerBound', $mapperCompiler, $validatorCompiler);

        $validator->map(5.0);
        $validator->map(6.0);

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected value greater than or equal to 5, got 4.0',
            static fn() => $validator->map(4.0),
        );
    }

    public function testFloatRangeValidatorWithExclusiveLowerBound(): void
    {
        $mapperCompiler = new MapFloat();
        $validatorCompiler = new AssertFloatRange(gt: 5.0);
        $validator = $this->compileValidator('FloatRangeValidatorWithExclusiveLowerBound', $mapperCompiler, $validatorCompiler);

        $validator->map(6.0);

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected value greater than 5, got 5.0',
            static fn() => $validator->map(5.0),
        );
    }

    public function testFloatRangeValidatorWithInclusiveUpperBound(): void
    {
        $mapperCompiler = new MapFloat();
        $validatorCompiler = new AssertFloatRange(lte: 5.0);
        $validator = $this->compileValidator('FloatRangeValidatorWithInclusiveUpperBound', $mapperCompiler, $validatorCompiler);

        $validator->map(5.0);
        $validator->map(4.0);

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected value less than or equal to 5, got 6.0',
            static fn() => $validator->map(6.0),
        );
    }

    public function testFloatRangeValidatorWithExclusiveUpperBound(): void
    {
        $mapperCompiler = new MapFloat();
        $validatorCompiler = new AssertFloatRange(lt: 5.0);
        $validator = $this->compileValidator('FloatRangeValidatorWithExclusiveUpperBound', $mapperCompiler, $validatorCompiler);

        $validator->map(4.0);

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected value less than 5, got 5.0',
            static fn() => $validator->map(5.0),
        );
    }

    public function testFloatRangeValidatorWithInclusiveLowerAndUpperBound(): void
    {
        $mapperCompiler = new MapFloat();
        $validatorCompiler = new AssertFloatRange(gte: 5.0, lte: 10.0);
        $validator = $this->compileValidator('FloatRangeValidatorWithInclusiveLowerAndUpperBound', $mapperCompiler, $validatorCompiler);

        $validator->map(5.0);
        $validator->map(6.0);
        $validator->map(10.0);

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
