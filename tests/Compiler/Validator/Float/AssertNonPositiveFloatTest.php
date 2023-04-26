<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Validator\Float;

use ShipMonk\InputMapper\Compiler\Validator\Float\AssertNonPositiveFloat;
use ShipMonkTests\InputMapper\Compiler\Validator\ValidatorCompilerTestCase;

class AssertNonPositiveFloatTest extends ValidatorCompilerTestCase
{

    public function testNonPositiveFloatValidator(): void
    {
        $validatorCompiler = new AssertNonPositiveFloat();
        self::assertNull($validatorCompiler->gte);
        self::assertNull($validatorCompiler->gt);
        self::assertNull($validatorCompiler->lt);
        self::assertSame(0.0, $validatorCompiler->lte);
    }

}
