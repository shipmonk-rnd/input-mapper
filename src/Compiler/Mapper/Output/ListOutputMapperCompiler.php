<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper\Output;

use PhpParser\Node\Expr;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\CompiledExpr;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\PassthroughMapperCompiler;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;

class ListOutputMapperCompiler implements MapperCompiler
{

    public function __construct(
        public readonly MapperCompiler $itemMapperCompiler,
    )
    {
    }

    public function compile(
        Expr $value,
        Expr $path,
        PhpCodeBuilder $builder,
    ): CompiledExpr
    {
        if ($this->itemMapperCompiler instanceof PassthroughMapperCompiler) {
            return new CompiledExpr($value);
        }

        [$indexVariableName, $itemVariableName, $mappedVariableName] = $builder->uniqVariableNames('index', 'item', 'mapped');

        $itemValue = $builder->var($itemVariableName);
        $itemPath = $builder->arrayImmutableAppend($path, $builder->var($indexVariableName));
        $itemMapper = $this->itemMapperCompiler->compile($itemValue, $itemPath, $builder);

        $foreachKey = $builder->var($indexVariableName);

        $statements = [
            $builder->assign($builder->var($mappedVariableName), $builder->val([])),

            $builder->foreach($value, $builder->var($itemVariableName), $foreachKey, [
                ...$itemMapper->statements,
                $builder->assign($builder->arrayDimFetch($builder->var($mappedVariableName)), $itemMapper->expr),
            ]),
        ];

        return new CompiledExpr($builder->var($mappedVariableName), $statements);
    }

    public function getInputType(): TypeNode
    {
        $itemType = $this->itemMapperCompiler->getInputType();
        return new GenericTypeNode(new IdentifierTypeNode('list'), [$itemType]);
    }

    public function getOutputType(): TypeNode
    {
        $itemType = $this->itemMapperCompiler->getOutputType();
        return new GenericTypeNode(new IdentifierTypeNode('list'), [$itemType]);
    }

}
