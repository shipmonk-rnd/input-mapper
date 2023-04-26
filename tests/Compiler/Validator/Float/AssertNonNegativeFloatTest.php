<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Validator\Float;

use ShipMonk\InputMapper\Compiler\Validator\Float\AssertNonNegativeFloat;
use ShipMonkTests\InputMapper\Compiler\Validator\ValidatorCompilerTestCase;

class AssertNonNegativeFloatTest extends ValidatorCompilerTestCase
{

    public function testNonNegativeFloatValidator(): void
    {
        $validatorCompiler = new AssertNonNegativeFloat();
        self::assertSame(0.0, $validatorCompiler->gte);
        self::assertNull($validatorCompiler->gt);
        self::assertNull($validatorCompiler->lt);
        self::assertNull($validatorCompiler->lte);
    }

}
