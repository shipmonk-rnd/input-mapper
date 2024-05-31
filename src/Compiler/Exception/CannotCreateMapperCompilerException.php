<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Exception;

use LogicException;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ReflectionParameter;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\UndefinedAwareMapperCompiler;
use ShipMonk\InputMapper\Compiler\Validator\ValidatorCompiler;
use Throwable;

class CannotCreateMapperCompilerException extends LogicException
{

    public static function fromType(TypeNode $type, ?string $reason = null, ?Throwable $previous = null): self
    {
        $reason = $reason !== null ? ", because {$reason}" : '';
        return new self("Cannot create mapper for type {$type}{$reason}", 0, $previous);
    }

    public static function withIncompatibleMapperForMethodParameter(
        MapperCompiler $mapperCompiler,
        ReflectionParameter $parameter,
        TypeNode $parameterType,
        ?Throwable $previous = null
    ): self
    {
        $mapperCompilerClass = $mapperCompiler::class;
        $mapperOutputType = $mapperCompiler->getOutputType();

        $parameterName = $parameter->getName();
        $className = $parameter->getDeclaringClass()?->getName();
        $methodName = $parameter->getDeclaringFunction()->getName();
        $methodFullName = $className !== null ? "{$className}::{$methodName}" : $methodName;

        $reason = "mapper output type '{$mapperOutputType}' is not compatible with parameter type '{$parameterType}'";
        return new self("Cannot use mapper {$mapperCompilerClass} for parameter \${$parameterName} of method {$methodFullName}, because {$reason}", 0, $previous);
    }

    public static function withIncompatibleDefaultValueParameter(
        UndefinedAwareMapperCompiler $mapperCompiler,
        ReflectionParameter $parameter,
        TypeNode $parameterType,
        ?Throwable $previous = null
    ): self
    {
        $mapperCompilerClass = $mapperCompiler::class;
        $defaultValueType = $mapperCompiler->getDefaultValueType();

        $parameterName = $parameter->getName();
        $className = $parameter->getDeclaringClass()?->getName();
        $methodName = $parameter->getDeclaringFunction()->getName();
        $methodFullName = $className !== null ? "{$className}::{$methodName}" : $methodName;

        $reason = "default value of type '{$defaultValueType}' is not compatible with parameter type '{$parameterType}'";
        return new self("Cannot use mapper {$mapperCompilerClass} for parameter \${$parameterName} of method {$methodFullName}, because {$reason}", 0, $previous);
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
