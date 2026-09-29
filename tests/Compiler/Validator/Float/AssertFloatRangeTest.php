<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Validator\Float;

use LogicException;
use ShipMonk\InputMapper\Compiler\Mapper\Input\FloatInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Validator\Float\AssertFloatRange;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapperTests\Compiler\Validator\ValidatorCompilerTestCase;

class AssertFloatRangeTest extends ValidatorCompilerTestCase
{

    public function testNoopFloatRangeValidator(): void
    {
        $mapperCompiler = new FloatInputMapperCompiler();
        $validatorCompiler = new AssertFloatRange();
        $validator = $this->compileValidator('NoopFloatRangeValidator', $mapperCompiler, $validatorCompiler);

        $validator->map(123);
        $validator->map(1.2);
        self::assertTrue(true); // @phpstan-ignore-line always true
    }

    public function testFloatRangeValidatorWithInclusiveLowerBound(): void
    {
        $mapperCompiler = new FloatInputMapperCompiler();
        $validatorCompiler = new AssertFloatRange(gte: 5.0);
        $validator = $this->compileValidator('FloatRangeValidatorWithInclusiveLowerBound', $mapperCompiler, $validatorCompiler);

        $validator->map(5.0);
        $validator->map(6.0);

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected value greater than or equal to 5, got 4.0',
            static fn () => $validator->map(4.0),
        );
    }

    public function testFloatRangeValidatorWithExclusiveLowerBound(): void
    {
        $mapperCompiler = new FloatInputMapperCompiler();
        $validatorCompiler = new AssertFloatRange(gt: 5.0);
        $validator = $this->compileValidator('FloatRangeValidatorWithExclusiveLowerBound', $mapperCompiler, $validatorCompiler);

        $validator->map(6.0);

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected value greater than 5, got 5.0',
            static fn () => $validator->map(5.0),
        );
    }

    public function testFloatRangeValidatorWithInclusiveUpperBound(): void
    {
        $mapperCompiler = new FloatInputMapperCompiler();
        $validatorCompiler = new AssertFloatRange(lte: 5.0);
        $validator = $this->compileValidator('FloatRangeValidatorWithInclusiveUpperBound', $mapperCompiler, $validatorCompiler);

        $validator->map(5.0);
        $validator->map(4.0);

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected value less than or equal to 5, got 6.0',
            static fn () => $validator->map(6.0),
        );
    }

    public function testFloatRangeValidatorWithExclusiveUpperBound(): void
    {
        $mapperCompiler = new FloatInputMapperCompiler();
        $validatorCompiler = new AssertFloatRange(lt: 5.0);
        $validator = $this->compileValidator('FloatRangeValidatorWithExclusiveUpperBound', $mapperCompiler, $validatorCompiler);

        $validator->map(4.0);

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected value less than 5, got 5.0',
            static fn () => $validator->map(5.0),
        );
    }

    public function testFloatRangeValidatorWithInclusiveLowerAndUpperBound(): void
    {
        $mapperCompiler = new FloatInputMapperCompiler();
        $validatorCompiler = new AssertFloatRange(gte: 5.0, lte: 10.0);
        $validator = $this->compileValidator('FloatRangeValidatorWithInclusiveLowerAndUpperBound', $mapperCompiler, $validatorCompiler);

        $validator->map(5.0);
        $validator->map(6.0);
        $validator->map(10.0);

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected value greater than or equal to 5, got 4.0',
            static fn () => $validator->map(4.0),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected value less than or equal to 10, got 11.0',
            static fn () => $validator->map(11.0),
        );
    }

    public function testBoundsThatAcceptNoFloat(): void
    {
        self::assertException(
            LogicException::class,
            'Bounds gte: 10, lte: 5 accept no float, so every input would fail',
            static fn () => new AssertFloatRange(gte: 10.0, lte: 5.0),
        );

        self::assertException(
            LogicException::class,
            'Bounds gt: 1, lt: 1 accept no float, so every input would fail',
            static fn () => new AssertFloatRange(gt: 1.0, lt: 1.0),
        );
    }

    public function testBoundsThatAcceptASingleFloat(): void
    {
        $validatorCompiler = new AssertFloatRange(gte: 1.0, lte: 1.0);

        self::assertSame(1.0, $validatorCompiler->gte);
        self::assertSame(1.0, $validatorCompiler->lte);
    }

}
