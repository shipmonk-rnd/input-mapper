<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper\Wrapper;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Variable;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\CompiledExpr;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use function count;

class ChainMapperCompiler implements MapperCompiler
{

    /**
     * @param list<MapperCompiler> $mapperCompilers
     */
    public function __construct(
        public readonly array $mapperCompilers,
    )
    {
    }

    public function compile(Expr $value, Expr $path, PhpCodeBuilder $builder): CompiledExpr
    {
        $statements = [];
        $mappedVariableName = $builder->uniqVariableName('mapped');
        $lastIndex = count($this->mapperCompilers) - 1;

        foreach ($this->mapperCompilers as $index => $mapperCompiler) {
            $mapper = $mapperCompiler->compile($value, $path, $builder);

            foreach ($mapper->statements as $statement) {
                $statements[] = $statement;
            }

            if ($mapper->expr instanceof Variable || $index === $lastIndex) {
                $value = $mapper->expr;

            } else {
                $statements[] = $builder->assign($builder->var($mappedVariableName), $mapper->expr);
                $value = $builder->var($mappedVariableName);
            }
        }

        return new CompiledExpr($value, $statements);
    }

    public function getInputType(): TypeNode
    {
        $first = 0;
        return $this->mapperCompilers[$first]->getInputType();
    }

    public function getOutputType(): TypeNode
    {
        $last = count($this->mapperCompilers) - 1;
        return $this->mapperCompilers[$last]->getOutputType();
    }

}
