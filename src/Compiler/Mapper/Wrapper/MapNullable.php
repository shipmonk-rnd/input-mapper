<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper\Wrapper;

use Attribute;
use PhpParser\Node\Expr;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\CompiledExpr;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Compiler\Type\PhpDocTypeUtils;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class MapNullable implements MapperCompiler
{

    public function __construct(
        public readonly MapperCompiler $innerMapperCompiler,
    )
    {
    }

    public function compile(Expr $value, Expr $path, PhpCodeBuilder $builder): CompiledExpr
    {
        $mapper = $this->innerMapperCompiler->compile($value, $path, $builder);
        $mappedVariableName = $builder->uniqVariableName('mapped');

        $statements = [
            $builder->if(
                $builder->same($value, $builder->val(null)),
                [
                    $builder->assign($builder->var($mappedVariableName), $builder->val(null)),
                ],
                [
                    ...$mapper->statements,
                    $builder->assign($builder->var($mappedVariableName), $mapper->expr),
                ],
            ),
        ];

        return new CompiledExpr($builder->var($mappedVariableName), $statements);
    }

    public function getInputType(): TypeNode
    {
        return PhpDocTypeUtils::makeNullable($this->innerMapperCompiler->getInputType());
    }

    public function getOutputType(): TypeNode
    {
        return PhpDocTypeUtils::makeNullable($this->innerMapperCompiler->getOutputType());
    }

}
