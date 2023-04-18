<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Validator\Int;

use ShipMonk\InputMapper\Compiler\Validator\Int\AssertInt32;
use ShipMonkTests\InputMapper\Compiler\Validator\ValidatorCompilerTestCase;

class AssertInt32Test extends ValidatorCompilerTestCase
{

    public function testInt32Validator(): void
    {
        $validatorCompiler = new AssertInt32();
        self::assertSame(-2_147_483_648, $validatorCompiler->gte);
        self::assertNull($validatorCompiler->gt);
        self::assertNull($validatorCompiler->lt);
        self::assertSame(+2_147_483_647, $validatorCompiler->lte);
    }

}
