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
        private readonly MapperCompiler $mapperCompiler,
        private readonly array $validatorCompilers,
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
            foreach ($validatorCompiler->compileValidator($mapperVariable, $path, $builder) as $statement) {
                $statements[] = $statement;
            }
        }

        return new CompiledExpr($mapperVariable, $statements);
    }

    /**
     * @return array<string, mixed>
     */
    public function getJsonSchema(): array
    {
        $schema = $this->mapperCompiler->getJsonSchema();

        foreach ($this->validatorCompilers as $validatorCompiler) {
            $schema = $validatorCompiler->toJsonSchema($schema);
        }

        return $schema;
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
