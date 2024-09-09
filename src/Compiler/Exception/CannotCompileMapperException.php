<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Exception;

use LogicException;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Object\MapDiscriminatedObject;
use ShipMonk\InputMapper\Compiler\Validator\ValidatorCompiler;
use Throwable;

class CannotCompileMapperException extends LogicException
{

    public static function withIncompatibleMapper(
        MapperCompiler $mapperCompiler,
        TypeNode $inputType,
        ?Throwable $previous = null
    ): self
    {
        $mapperCompilerClass = $mapperCompiler::class;
        $mapperInputType = $mapperCompiler->getInputType();

        $reason = "its input type '{$mapperInputType}' is not super type of '{$inputType}'";
        return new self("Cannot compile mapper {$mapperCompilerClass}, because {$reason}", 0, $previous);
    }

    /**
     * @template T of object
     * @param MapDiscriminatedObject<T> $mapperCompiler
     */
    public static function withIncompatibleSubtypeMapper(
        MapDiscriminatedObject $mapperCompiler,
        MapperCompiler $subtypeMapperCompiler,
        ?Throwable $previous = null
    ): self
    {
        $mapperOutputType = $mapperCompiler->getOutputType();
        $subtypeMapperCompilerClass = $subtypeMapperCompiler::class;
        $subtypeMapperOutputType = $subtypeMapperCompiler->getOutputType();

        $reason = "its output type '{$subtypeMapperOutputType}' is not super type of '{$mapperOutputType}'";
        return new self("Cannot compile mapper {$subtypeMapperCompilerClass} as subtype (#[Discriminator]) mapper, because {$reason}", 0, $previous);
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
        $reason = "mapper output type '{$mapperOutputType}' is not compatible with validator input type '{$validatorInputType}'";
        return new self("Cannot compile mapper with validator {$validatorCompilerClass}, because {$reason}", 0, $previous);
    }

}
