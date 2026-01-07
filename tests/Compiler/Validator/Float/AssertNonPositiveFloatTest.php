<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Validator\Float;

use ShipMonk\InputMapper\Compiler\Validator\Float\AssertNonPositiveFloat;
use ShipMonk\InputMapperTests\Compiler\Validator\ValidatorCompilerTestCase;

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
