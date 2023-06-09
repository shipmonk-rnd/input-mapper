<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper\Wrapper;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Variable;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\CompiledExpr;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Compiler\Validator\ValidatorCompiler;

class ValidatedMapperCompiler implements MapperCompiler
{

    /**
     * @param list<ValidatorCompiler> $validatorCompilers
     */
    public function __construct(
        public readonly MapperCompiler $mapperCompiler,
        public readonly array $validatorCompilers,
    )
    {
    }

    public function compile(Expr $value, Expr $path, PhpCodeBuilder $builder): CompiledExpr
    {
        $mapper = $this->mapperCompiler->compile($value, $path, $builder);
        $statements = $mapper->statements;

        if ($mapper->expr instanceof Variable) {
            $mapperVariable = $mapper->expr;

        } else {
            $mappedVariableName = $builder->uniqVariableName('mapped');
            $mapperVariable = $builder->var($mappedVariableName);
            $statements[] = $builder->assign($mapperVariable, $mapper->expr);
        }

        foreach ($this->validatorCompilers as $validatorCompiler) {
            foreach ($validatorCompiler->compile($mapperVariable, $path, $builder) as $statement) {
                $statements[] = $statement;
            }
        }

        return new CompiledExpr($mapperVariable, $statements);
    }

    public function getInputType(PhpCodeBuilder $builder): TypeNode
    {
        return $this->mapperCompiler->getInputType($builder);
    }

    public function getOutputType(PhpCodeBuilder $builder): TypeNode
    {
        return $this->mapperCompiler->getOutputType($builder);
    }

}
