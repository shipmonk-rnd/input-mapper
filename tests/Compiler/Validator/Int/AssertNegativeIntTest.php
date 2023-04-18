<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Validator\Int;

use ShipMonk\InputMapper\Compiler\Validator\Int\AssertNegativeInt;
use ShipMonkTests\InputMapper\Compiler\Validator\ValidatorCompilerTestCase;

class AssertNegativeIntTest extends ValidatorCompilerTestCase
{

    public function testNegativeIntValidator(): void
    {
        $validatorCompiler = new AssertNegativeInt();
        self::assertNull($validatorCompiler->gte);
        self::assertNull($validatorCompiler->gt);
        self::assertSame(0, $validatorCompiler->lt);
        self::assertNull($validatorCompiler->lte);
    }

}
