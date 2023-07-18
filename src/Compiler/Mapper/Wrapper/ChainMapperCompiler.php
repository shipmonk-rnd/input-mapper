<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper\Wrapper;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Variable;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\CompiledExpr;
use ShipMonk\InputMapper\Compiler\Exception\CannotCompileMapperException;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Compiler\Type\PhpDocTypeUtils;
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
        $mapperOutputType = null;

        foreach ($this->mapperCompilers as $index => $mapperCompiler) {
            $mapperInputType = $mapperCompiler->getInputType();

            if ($mapperOutputType !== null && !PhpDocTypeUtils::isSubTypeOf($mapperOutputType, $mapperInputType)) {
                throw CannotCompileMapperException::withIncompatibleMapper($mapperCompiler, $mapperOutputType);
            }

            $mapper = $mapperCompiler->compile($value, $path, $builder);
            $mapperOutputType = $mapperCompiler->getOutputType();

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
