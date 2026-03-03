<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Validator\Int;

use ShipMonk\InputMapper\Compiler\Attribute\MapInt;
use ShipMonk\InputMapper\Compiler\Validator\Int\AssertNonNegativeInt;
use ShipMonk\InputMapperTests\Compiler\Validator\ValidatorCompilerTestCase;

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
