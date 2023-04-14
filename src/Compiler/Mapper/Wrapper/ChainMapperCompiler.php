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
        private readonly array $mapperCompilers,
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

    /**
     * @return array<string, mixed>
     */
    public function getJsonSchema(): array
    {
        return $this->mapperCompilers[0]->getJsonSchema();
    }

    public function getInputType(PhpCodeBuilder $builder): TypeNode
    {
        $first = 0;
        return $this->mapperCompilers[$first]->getInputType($builder);
    }

    public function getOutputType(PhpCodeBuilder $builder): TypeNode
    {
        $last = count($this->mapperCompilers) - 1;
        return $this->mapperCompilers[$last]->getOutputType($builder);
    }

}
