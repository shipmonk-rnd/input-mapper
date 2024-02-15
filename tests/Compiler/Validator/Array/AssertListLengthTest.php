<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Validator\Array;

use LogicException;
use ShipMonk\InputMapper\Compiler\Mapper\Array\MapList;
use ShipMonk\InputMapper\Compiler\Mapper\Mixed\MapMixed;
use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapString;
use ShipMonk\InputMapper\Compiler\Validator\Array\AssertListLength;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonkTests\InputMapper\Compiler\Validator\ValidatorCompilerTestCase;

class AssertListLengthTest extends ValidatorCompilerTestCase
{

    public function testNoopListLengthValidator(): void
    {
        $mapperCompiler = new MapList(new MapMixed());
        $validatorCompiler = new AssertListLength();
        $validator = $this->compileValidator('NoopListLengthValidator', $mapperCompiler, $validatorCompiler);

        $validator->map(['abc']);
        self::assertTrue(true); // @phpstan-ignore-line always true
    }

    public function testListLengthValidatorWithMin(): void
    {
        $mapperCompiler = new MapList(new MapString());
        $validatorCompiler = new AssertListLength(min: 2);
        $validator = $this->compileValidator('ListLengthValidatorWithMin', $mapperCompiler, $validatorCompiler);

        $validator->map(['a', 'b']);
        $validator->map(['a', 'b', 'c']);

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected list with at least 2 items, got array',
            static fn() => $validator->map(['a']),
        );
    }

    public function testListLengthValidatorWithMax(): void
    {
        $mapperCompiler = new MapList(new MapMixed());
        $validatorCompiler = new AssertListLength(max: 5);
        $validator = $this->compileValidator('ListLengthValidatorWithMax', $mapperCompiler, $validatorCompiler);

        $validator->map(['a', 'b']);
        $validator->map(['a', 'b', 'c', 'd', 'e']);

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected list with at most 5 items, got array',
            static fn() => $validator->map(['a', 'b', 'c', 'd', 'e', 'f']),
        );
    }

    public function testListLengthValidatorWithMinAndMax(): void
    {
        $mapperCompiler = new MapList(new MapMixed());
        $validatorCompiler = new AssertListLength(min: 1, max: 5);
        $validator = $this->compileValidator('ListLengthValidatorWithMinAndMax', $mapperCompiler, $validatorCompiler);

        $validator->map(['a']);
        $validator->map(['a', 'b', 'c']);
        $validator->map(['a', 'b', 'c', 'd', 'e']);

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected list with at least 1 items, got array',
            static fn() => $validator->map([]),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected list with at most 5 items, got array',
            static fn() => $validator->map(['a', 'b', 'c', 'd', 'e', 'f']),
        );
    }

    public function testListLengthValidatorWithExact(): void
    {
        $mapperCompiler = new MapList(new MapMixed());
        $validatorCompiler = new AssertListLength(exact: 5);
        $validator = $this->compileValidator('ListLengthValidatorWithExact', $mapperCompiler, $validatorCompiler);

        $validator->map(['a', 'b', 'c', 'd', 'e']);

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected list with exactly 5 items, got array',
            static fn() => $validator->map([]),
        );
    }

    public function testInvalidCombinationOfExactWithMinMax(): void
    {
        self::assertException(
            LogicException::class,
            'Cannot use "exact" and "min" or "max" at the same time',
            static fn() => new AssertListLength(exact: 5, min: 1),
        );
    }

}
