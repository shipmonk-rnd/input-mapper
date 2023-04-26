<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Validator\Float;

use ShipMonk\InputMapper\Compiler\Validator\Float\AssertPositiveFloat;
use ShipMonkTests\InputMapper\Compiler\Validator\ValidatorCompilerTestCase;

class AssertPositiveFloatTest extends ValidatorCompilerTestCase
{

    public function testPositiveFloatValidator(): void
    {
        $validatorCompiler = new AssertPositiveFloat();
        self::assertNull($validatorCompiler->gte);
        self::assertSame(0.0, $validatorCompiler->gt);
        self::assertNull($validatorCompiler->lt);
        self::assertNull($validatorCompiler->lte);
    }

}
