<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Validator\Float;

use ShipMonk\InputMapper\Compiler\Validator\Float\AssertFloatMultipleOf;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonkTests\InputMapper\Compiler\Validator\ValidatorCompilerTestCase;

class AssertFloatMultipleOfTest extends ValidatorCompilerTestCase
{

    public function testFloatWithAtMostTwoDecimalPlaces(): void
    {
        $validatorCompiler = new AssertFloatMultipleOf(0.01);
        $validator = $this->compileValidator('FloatWithAtMostTwoDecimalPlaces', $validatorCompiler);

        $validator->map(+1.0);
        $validator->map(+1.2);
        $validator->map(+1.23);

        $validator->map(-1.0);
        $validator->map(-1.2);
        $validator->map(-1.23);

        $validator->map(null);
        $validator->map([]);

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected multiple of 0.01, got 1.234',
            static fn() => $validator->map(1.234),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected multiple of 0.01, got 1.23000000004',
            static fn() => $validator->map(1.230_000_000_04),
        );
    }

    public function testFloatMultipleOfFive(): void
    {
        $validatorCompiler = new AssertFloatMultipleOf(5.0);
        $validator = $this->compileValidator('FloatMultipleOfFive', $validatorCompiler);

        $validator->map(+5.0);
        $validator->map(+65.0);

        $validator->map(-5.0);
        $validator->map(-65.0);

        $validator->map(7);
        $validator->map(null);
        $validator->map([]);

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected multiple of 5, got 1.0',
            static fn() => $validator->map(1.0),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected multiple of 5, got 1.234',
            static fn() => $validator->map(1.234),
        );
    }

}
