<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Validator\Array;

use ShipMonk\InputMapper\Compiler\Validator\Array\AssertListItem;
use ShipMonk\InputMapper\Compiler\Validator\Int\AssertPositiveInt;
use ShipMonk\InputMapper\Compiler\Validator\String\AssertStringLength;
use ShipMonk\InputMapper\Runtime\MappingFailedException;
use ShipMonkTests\InputMapper\Compiler\Validator\ValidatorCompilerTestCase;

class AssertListItemTest extends ValidatorCompilerTestCase
{

    public function testListItemValidator(): void
    {
        $validatorCompiler = new AssertListItem([new AssertPositiveInt()]);
        $validator = $this->compileValidator('ListItemValidator', $validatorCompiler);

        $validator->map([]);
        $validator->map([1, 'abc', null]);
        $validator->map('abc');
        $validator->map(null);

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /2: Expected value greater than 0, got 0',
            static fn() => $validator->map([1, 2, 0]),
        );
    }

    public function testListItemValidatorWithMultipleValidators(): void
    {
        $validatorCompiler = new AssertListItem([new AssertPositiveInt(), new AssertStringLength(exact: 5)]);
        $validator = $this->compileValidator('ListItemValidatorWithMultipleValidators', $validatorCompiler);

        $validator->map([]);
        $validator->map([1, 'hello', null]);
        $validator->map('abc');
        $validator->map(null);

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /2: Expected value greater than 0, got 0',
            static fn() => $validator->map([1, 2, 0]),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /2: Expected string with exactly 5 characters, got "abc"',
            static fn() => $validator->map([1, 2, 'abc']),
        );
    }

}
