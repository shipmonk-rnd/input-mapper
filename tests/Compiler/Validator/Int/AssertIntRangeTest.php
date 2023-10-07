<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Validator\Int;

use PHPUnit\Framework\Attributes\DataProvider;
use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapInt;
use ShipMonk\InputMapper\Compiler\Validator\Int\AssertIntRange;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonkTests\InputMapper\Compiler\Validator\ValidatorCompilerTestCase;
use const PHP_INT_MAX;
use const PHP_INT_MIN;

class AssertIntRangeTest extends ValidatorCompilerTestCase
{

    public function testNoopIntRangeValidator(): void
    {
        $mapperCompiler = new MapInt();
        $validatorCompiler = new AssertIntRange();
        $validator = $this->compileValidator('NoopIntRangeValidator', $mapperCompiler, $validatorCompiler);

        $validator->map(123);
        self::assertTrue(true); // @phpstan-ignore-line always true
    }

    public function testIntRangeValidatorWithInclusiveLowerBound(): void
    {
        $mapperCompiler = new MapInt();
        $validatorCompiler = new AssertIntRange(gte: 5);
        $validator = $this->compileValidator('IntRangeValidatorWithInclusiveLowerBound', $mapperCompiler, $validatorCompiler);

        $validator->map(5);
        $validator->map(6);

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected value greater than or equal to 5, got 4',
            static fn() => $validator->map(4),
        );
    }

    public function testIntRangeValidatorWithExclusiveLowerBound(): void
    {
        $mapperCompiler = new MapInt();
        $validatorCompiler = new AssertIntRange(gt: 5);
        $validator = $this->compileValidator('IntRangeValidatorWithExclusiveLowerBound', $mapperCompiler, $validatorCompiler);

        $validator->map(6);

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected value greater than 5, got 5',
            static fn() => $validator->map(5),
        );
    }

    public function testIntRangeValidatorWithInclusiveUpperBound(): void
    {
        $mapperCompiler = new MapInt();
        $validatorCompiler = new AssertIntRange(lte: 5);
        $validator = $this->compileValidator('IntRangeValidatorWithInclusiveUpperBound', $mapperCompiler, $validatorCompiler);

        $validator->map(5);
        $validator->map(4);

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected value less than or equal to 5, got 6',
            static fn() => $validator->map(6),
        );
    }

    public function testIntRangeValidatorWithExclusiveUpperBound(): void
    {
        $mapperCompiler = new MapInt();
        $validatorCompiler = new AssertIntRange(lt: 5);
        $validator = $this->compileValidator('IntRangeValidatorWithExclusiveUpperBound', $mapperCompiler, $validatorCompiler);

        $validator->map(4);

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected value less than 5, got 5',
            static fn() => $validator->map(5),
        );
    }

    public function testIntRangeValidatorWithInclusiveLowerAndUpperBound(): void
    {
        $mapperCompiler = new MapInt();
        $validatorCompiler = new AssertIntRange(gte: 5, lte: 10);
        $validator = $this->compileValidator('IntRangeValidatorWithInclusiveLowerAndUpperBound', $mapperCompiler, $validatorCompiler);

        $validator->map(5);
        $validator->map(6);
        $validator->map(10);

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

    #[DataProvider('provideGetNarrowedInputTypeData')]
    public function testGetNarrowedInputType(AssertIntRange $validatorCompiler, string $expectedNarrowedType): void
    {
        self::assertSame($expectedNarrowedType, $validatorCompiler->getNarrowedInputType()->__toString());
    }

    /**
     * @return iterable<array{AssertIntRange, string}>
     */
    public static function provideGetNarrowedInputTypeData(): iterable
    {
        yield [
            new AssertIntRange(),
            'int',
        ];

        yield [
            new AssertIntRange(gte: 5),
            'int<5, max>',
        ];

        yield [
            new AssertIntRange(gt: 5),
            'int<6, max>',
        ];

        yield [
            new AssertIntRange(lte: 5),
            'int<min, 5>',
        ];

        yield [
            new AssertIntRange(lt: 5),
            'int<min, 4>',
        ];

        yield [
            new AssertIntRange(gte: 5, lte: 10),
            'int<5, 10>',
        ];

        yield [
            new AssertIntRange(gt: 5, lte: 10),
            'int<6, 10>',
        ];

        yield [
            new AssertIntRange(gte: 5, lt: 10),
            'int<5, 9>',
        ];

        yield [
            new AssertIntRange(gt: 5, lt: 10),
            'int<6, 9>',
        ];

        yield [
            new AssertIntRange(gt: 0, gte: 5),
            'int<5, max>',
        ];

        yield [
            new AssertIntRange(lt: 0, lte: 5),
            'int<min, -1>',
        ];

        yield [
            new AssertIntRange(gt: 5, gte: 0),
            'int<6, max>',
        ];

        yield [
            new AssertIntRange(lt: 5, lte: 0),
            'int<min, 0>',
        ];

        yield [
            new AssertIntRange(gte: PHP_INT_MIN, lte: PHP_INT_MAX),
            'int',
        ];
    }

}
