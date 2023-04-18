<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Validator\Int;

use ShipMonk\InputMapper\Compiler\Validator\Int\AssertInt16;
use ShipMonkTests\InputMapper\Compiler\Validator\ValidatorCompilerTestCase;

class AssertInt16Test extends ValidatorCompilerTestCase
{

    public function testInt16Validator(): void
    {
        $validatorCompiler = new AssertInt16();
        self::assertSame(-32_768, $validatorCompiler->gte);
        self::assertNull($validatorCompiler->lt);
        self::assertNull($validatorCompiler->gt);
        self::assertSame(+32_767, $validatorCompiler->lte);
    }

}
