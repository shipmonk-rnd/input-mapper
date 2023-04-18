<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Validator\Int;

use ShipMonk\InputMapper\Compiler\Validator\Int\AssertPositiveInt;
use ShipMonkTests\InputMapper\Compiler\Validator\ValidatorCompilerTestCase;

class AssertPositiveIntTest extends ValidatorCompilerTestCase
{

    public function testPositiveIntValidator(): void
    {
        $validatorCompiler = new AssertPositiveInt();
        self::assertNull($validatorCompiler->gte);
        self::assertSame(0, $validatorCompiler->gt);
        self::assertNull($validatorCompiler->lt);
        self::assertNull($validatorCompiler->lte);
    }

}
