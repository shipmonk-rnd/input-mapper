<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Validator\Int;

use ShipMonk\InputMapper\Compiler\Mapper\Input\IntInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Validator\Int\AssertIntMultipleOf;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapperTests\Compiler\Validator\ValidatorCompilerTestCase;

class AssertIntMultipleOfTest extends ValidatorCompilerTestCase
{

    public function testIntMultipleOfFive(): void
    {
        $mapperCompiler = new IntInputMapperCompiler();
        $validatorCompiler = new AssertIntMultipleOf(5);
        $validator = $this->compileValidator('IntMultipleOfFive', $mapperCompiler, $validatorCompiler);

        $validator->map(+5);
        $validator->map(+65);

        $validator->map(-5);
        $validator->map(-65);

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected multiple of 5, got 1',
            static fn () => $validator->map(1),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected multiple of 5, got 123',
            static fn () => $validator->map(123),
        );
    }

}
