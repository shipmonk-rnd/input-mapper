<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Validator\Int;

use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapInt;
use ShipMonk\InputMapper\Compiler\Validator\Int\AssertIntMultipleOf;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonkTests\InputMapper\Compiler\Validator\ValidatorCompilerTestCase;

class AssertIntMultipleOfTest extends ValidatorCompilerTestCase
{

    public function testIntMultipleOfFive(): void
    {
        $mapperCompiler = new MapInt();
        $validatorCompiler = new AssertIntMultipleOf(5);
        $validator = $this->compileValidator('IntMultipleOfFive', $mapperCompiler, $validatorCompiler);

        $validator->map(+5);
        $validator->map(+65);

        $validator->map(-5);
        $validator->map(-65);

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected multiple of 5, got 1',
            static fn() => $validator->map(1),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected multiple of 5, got 123',
            static fn() => $validator->map(123),
        );
    }

}
