<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Exception;

use LogicException;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Validator\ValidatorCompiler;
use Throwable;

class CannotCreateMapperCompilerException extends LogicException
{

    public static function fromType(TypeNode $type, ?string $reason = null, ?Throwable $previous = null): self
    {
        $reason = $reason !== null ? ", because {$reason}" : '';
        return new self("Cannot create mapper for type {$type}{$reason}", 0, $previous);
    }

    public static function withIncompatibleValidator(
        ValidatorCompiler $validatorCompiler,
        MapperCompiler $mapperCompiler,
        ?Throwable $previous = null
    ): self
    {
        $validatorCompilerClass = $validatorCompiler::class;
        $validatorInputType = $validatorCompiler->getInputType();
        $mapperOutputType = $mapperCompiler->getOutputType();
        $reason = "mapper output type {$mapperOutputType} is not compatible with validator input type {$validatorInputType}";
        return new self("Cannot create mapper with validator {$validatorCompilerClass}, because {$reason}", 0, $previous);
    }

}
