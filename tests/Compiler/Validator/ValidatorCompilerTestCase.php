<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Validator;

use ShipMonk\InputMapper\Compiler\Mapper\Mixed\MapMixed;
use ShipMonk\InputMapper\Compiler\Mapper\Wrapper\ValidatedMapperCompiler;
use ShipMonk\InputMapper\Compiler\Validator\ValidatorCompiler;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonkTests\InputMapper\Compiler\Mapper\MapperCompilerTestCase;

abstract class ValidatorCompilerTestCase extends MapperCompilerTestCase
{

    /**
     * @return Mapper<mixed>
     */
    protected function compileValidator(string $name, ValidatorCompiler $validatorCompiler): Mapper
    {
        $mapperCompiler = new ValidatedMapperCompiler(new MapMixed(), [$validatorCompiler]);
        return $this->compileMapper($name, $mapperCompiler);
    }

}
