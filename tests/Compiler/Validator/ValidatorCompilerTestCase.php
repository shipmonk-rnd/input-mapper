<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Validator;

use ShipMonk\InputMapper\Compiler\Mapper\Input\ValidatedInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Validator\ValidatorCompiler;
use ShipMonk\InputMapper\Runtime\InputMapper;
use ShipMonk\InputMapperTests\Compiler\Mapper\MapperCompilerTestCase;

abstract class ValidatorCompilerTestCase extends MapperCompilerTestCase
{

    /**
     * @return InputMapper<mixed>
     */
    protected function compileValidator(
        string $name,
        MapperCompiler $mapperCompiler,
        ValidatorCompiler $validatorCompiler,
    ): InputMapper
    {
        $mapperCompiler = new ValidatedInputMapperCompiler($mapperCompiler, [$validatorCompiler]);
        return $this->compileMapper($name, $mapperCompiler);
    }

}
