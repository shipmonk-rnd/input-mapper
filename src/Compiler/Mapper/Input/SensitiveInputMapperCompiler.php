<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper\Input;

use PhpParser\Node\Expr;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\CompiledExpr;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;

class SensitiveInputMapperCompiler implements MapperCompiler
{

    public function __construct(
        public readonly MapperCompiler $mapperCompiler,
    )
    {
    }

    public function compile(
        Expr $value,
        Expr $path,
        PhpCodeBuilder $builder,
    ): CompiledExpr
    {
        $mapper = $this->mapperCompiler->compile($value, $path, $builder);
        $mappedVariable = $builder->var($builder->uniqVariableName('mapped'));
        $exceptionVariable = $builder->var($builder->uniqVariableName('e'));

        $exceptionClassName = $builder->importClass(MappingFailedException::class);

        $statement = $builder->tryCatch(
            [...$mapper->statements, $builder->assign($mappedVariable, $mapper->expr)],
            $exceptionClassName,
            $exceptionVariable,
            [$builder->throw($builder->staticCall($exceptionClassName, 'redact', [$exceptionVariable]))],
        );

        return new CompiledExpr($mappedVariable, [$statement]);
    }

    public function getInputType(): TypeNode
    {
        return $this->mapperCompiler->getInputType();
    }

    public function getOutputType(): TypeNode
    {
        return $this->mapperCompiler->getOutputType();
    }

}
