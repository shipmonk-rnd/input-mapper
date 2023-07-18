<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Validator\String;

use LogicException;
use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapString;
use ShipMonk\InputMapper\Compiler\Validator\String\AssertStringLength;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonkTests\InputMapper\Compiler\Validator\ValidatorCompilerTestCase;

class AssertStringLengthTest extends ValidatorCompilerTestCase
{

    public function testNoopStringLengthValidator(): void
    {
        $mapperCompiler = new MapString();
        $validatorCompiler = new AssertStringLength();
        $validator = $this->compileValidator('NoopStringLengthValidator', $mapperCompiler, $validatorCompiler);

        $validator->map('abc');
        self::assertTrue(true); // @phpstan-ignore-line always true
    }

    public function testStringLengthValidatorWithMin(): void
    {
        $mapperCompiler = new MapString();
        $validatorCompiler = new AssertStringLength(min: 5);
        $validator = $this->compileValidator('StringLengthValidatorWithMin', $mapperCompiler, $validatorCompiler);

        $validator->map('hello');
        $validator->map('hello world');

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected string with at least 5 characters, got "abcd"',
            static fn() => $validator->map('abcd'),
        );
    }

    public function testStringLengthValidatorWithMax(): void
    {
        $mapperCompiler = new MapString();
        $validatorCompiler = new AssertStringLength(max: 5);
        $validator = $this->compileValidator('StringLengthValidatorWithMax', $mapperCompiler, $validatorCompiler);

        $validator->map('abc');
        $validator->map('hello');

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected string with at most 5 characters, got "abcdef"',
            static fn() => $validator->map('abcdef'),
        );
    }

    public function testStringLengthValidatorWithMinAndMax(): void
    {
        $mapperCompiler = new MapString();
        $validatorCompiler = new AssertStringLength(min: 1, max: 5);
        $validator = $this->compileValidator('StringLengthValidatorWithMinAndMax', $mapperCompiler, $validatorCompiler);

        $validator->map('a');
        $validator->map('abc');
        $validator->map('hello');

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected string with at least 1 characters, got ""',
            static fn() => $validator->map(''),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected string with at most 5 characters, got "abcdef"',
            static fn() => $validator->map('abcdef'),
        );
    }

    public function testStringLengthValidatorWithExact(): void
    {
        $mapperCompiler = new MapString();
        $validatorCompiler = new AssertStringLength(exact: 5);
        $validator = $this->compileValidator('StringLengthValidatorWithExact', $mapperCompiler, $validatorCompiler);

        $validator->map('hello');

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected string with exactly 5 characters, got ""',
            static fn() => $validator->map(''),
        );
    }

    public function testInvalidCombinationOfExactWithMinMax(): void
    {
        self::assertException(
            LogicException::class,
            'Cannot use "exact" and "min" or "max" at the same time',
            static fn() => new AssertStringLength(exact: 5, min: 1),
        );
    }

}
