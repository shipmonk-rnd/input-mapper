<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Validator\Float;

use ShipMonk\InputMapper\Compiler\Validator\Float\AssertNegativeFloat;
use ShipMonkTests\InputMapper\Compiler\Validator\ValidatorCompilerTestCase;

class AssertNegativeFloatTest extends ValidatorCompilerTestCase
{

    public function testNegativeFloatValidator(): void
    {
        $validatorCompiler = new AssertNegativeFloat();
        self::assertNull($validatorCompiler->gte);
        self::assertNull($validatorCompiler->gt);
        self::assertSame(0.0, $validatorCompiler->lt);
        self::assertNull($validatorCompiler->lte);
    }

}
