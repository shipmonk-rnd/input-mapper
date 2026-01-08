<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Validator\Int;

use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapInt;
use ShipMonk\InputMapper\Compiler\Validator\Int\AssertPositiveInt;
use ShipMonk\InputMapperTests\Compiler\Validator\ValidatorCompilerTestCase;

class AssertPositiveIntTest extends ValidatorCompilerTestCase
{

    public function testPositiveIntValidator(): void
    {
        $mapperCompiler = new MapInt();
        $validatorCompiler = new AssertPositiveInt();

        self::assertNull($validatorCompiler->gte);
        self::assertSame(0, $validatorCompiler->gt);
        self::assertNull($validatorCompiler->lt);
        self::assertNull($validatorCompiler->lte);

        $validator = $this->compileValidator('PositiveIntValidator', $mapperCompiler, $validatorCompiler);
        $validator->map(123);
    }

}
