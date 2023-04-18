<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Validator\String;

use ShipMonk\InputMapper\Compiler\Validator\String\AssertStringLength;
use ShipMonk\InputMapper\Runtime\MappingFailedException;
use ShipMonkTests\InputMapper\Compiler\Validator\ValidatorCompilerTestCase;

class AssertStringLengthTest extends ValidatorCompilerTestCase
{

    public function testNoopStringLengthValidator(): void
    {
        $validatorCompiler = new AssertStringLength();
        $validator = $this->compileValidator('NoopStringLengthValidator', $validatorCompiler);

        $validator->map('abc');
        $validator->map(123);
        $validator->map(null);
        $validator->map([]);
        self::assertTrue(true); // @phpstan-ignore-line always true
    }

    public function testStringLengthValidatorWithMin(): void
    {
        $validatorCompiler = new AssertStringLength(min: 5);
        $validator = $this->compileValidator('StringLengthValidatorWithMin', $validatorCompiler);

        $validator->map('hello');
        $validator->map('hello world');
        $validator->map(null);
        $validator->map([]);

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected string with at least 5 characters, got "abcd"',
            static fn() => $validator->map('abcd'),
        );
    }

    public function testStringLengthValidatorWithMax(): void
    {
        $validatorCompiler = new AssertStringLength(max: 5);
        $validator = $this->compileValidator('StringLengthValidatorWithMax', $validatorCompiler);

        $validator->map('abc');
        $validator->map('hello');
        $validator->map(null);
        $validator->map([]);

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected string with at most 5 characters, got "abcdef"',
            static fn() => $validator->map('abcdef'),
        );
    }

    public function testStringLengthValidatorWithMinAndMax(): void
    {
        $validatorCompiler = new AssertStringLength(min: 1, max: 5);
        $validator = $this->compileValidator('StringLengthValidatorWithMinAndMax', $validatorCompiler);

        $validator->map('a');
        $validator->map('abc');
        $validator->map('hello');
        $validator->map(null);
        $validator->map([]);

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
        $validatorCompiler = new AssertStringLength(exact: 5);
        $validator = $this->compileValidator('StringLengthValidatorWithExact', $validatorCompiler);

        $validator->map('hello');
        $validator->map(null);
        $validator->map([]);

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected string with exactly 5 characters, got ""',
            static fn() => $validator->map(''),
        );
    }

}
