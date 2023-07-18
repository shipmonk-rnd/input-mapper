<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Validator;

use ShipMonk\InputMapper\Compiler\Mapper\Mixed\MapMixed;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonkTests\InputMapper\Compiler\Validator\Data\AssertMultipleOfSeven;

class AssertRuntimeTest extends ValidatorCompilerTestCase
{

    public function testRuntimeValidator(): void
    {
        $mapperCompiler = new MapMixed();
        $validatorCompiler = new AssertMultipleOfSeven();
        $validator = $this->compileValidator('MultipleOfSevenValidator', $mapperCompiler, $validatorCompiler);

        $validator->map(0);
        $validator->map(7);
        $validator->map(14);
        $validator->map(null);
        $validator->map([]);

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected multiple of 7, got 1',
            static fn() => $validator->map(1),
        );
    }

}
