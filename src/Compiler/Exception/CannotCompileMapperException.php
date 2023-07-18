<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Exception;

use LogicException;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Validator\ValidatorCompiler;
use Throwable;

class CannotCompileMapperException extends LogicException
{

    public static function withIncompatibleValidator(
        ValidatorCompiler $validatorCompiler,
        MapperCompiler $mapperCompiler,
        ?Throwable $previous = null
    ): self
    {
        $validatorCompilerClass = $validatorCompiler::class;
        $validatorInputType = $validatorCompiler->getInputType();
        $mapperOutputType = $mapperCompiler->getOutputType();
        $reason = "mapper output type '{$mapperOutputType}' is not compatible with validator input type '{$validatorInputType}'";
        return new self("Cannot compile mapper with validator {$validatorCompilerClass}, because {$reason}", 0, $previous);
    }

}
