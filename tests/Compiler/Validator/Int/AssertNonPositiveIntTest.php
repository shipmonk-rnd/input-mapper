<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Validator\Int;

use ShipMonk\InputMapper\Compiler\Validator\Int\AssertNonPositiveInt;
use ShipMonkTests\InputMapper\Compiler\Validator\ValidatorCompilerTestCase;

class AssertNonPositiveIntTest extends ValidatorCompilerTestCase
{

    public function testNonPositiveIntValidator(): void
    {
        $validatorCompiler = new AssertNonPositiveInt();
        self::assertNull($validatorCompiler->gte);
        self::assertNull($validatorCompiler->gt);
        self::assertNull($validatorCompiler->lt);
        self::assertSame(0, $validatorCompiler->lte);
    }

}
