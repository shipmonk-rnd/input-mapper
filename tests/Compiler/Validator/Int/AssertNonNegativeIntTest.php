<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Validator\Int;

use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapInt;
use ShipMonk\InputMapper\Compiler\Validator\Int\AssertNonNegativeInt;
use ShipMonkTests\InputMapper\Compiler\Validator\ValidatorCompilerTestCase;

class AssertNonNegativeIntTest extends ValidatorCompilerTestCase
{

    public function testNonNegativeIntValidator(): void
    {
        $mapperCompiler = new MapInt();
        $validatorCompiler = new AssertNonNegativeInt();

        self::assertSame(0, $validatorCompiler->gte);
        self::assertNull($validatorCompiler->gt);
        self::assertNull($validatorCompiler->lt);
        self::assertNull($validatorCompiler->lte);

        $validator = $this->compileValidator('NonNegativeIntValidator', $mapperCompiler, $validatorCompiler);
        $validator->map(0);
        $validator->map(123);
    }

}
