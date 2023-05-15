<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper\Array;

use Attribute;
use PhpParser\Node\Expr;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\CompiledExpr;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;

#[Attribute(Attribute::TARGET_PARAMETER)]
class MapList implements MapperCompiler
{

    public function __construct(
        public readonly MapperCompiler $itemMapperCompiler,
    )
    {
    }

    public function compile(Expr $value, Expr $path, PhpCodeBuilder $builder): CompiledExpr
    {
        [$indexVariableName, $itemVariableName, $mappedVariableName] = $builder->uniqVariableNames('index', 'item', 'mapped');

        $itemValue = $builder->var($itemVariableName);
        $itemPath = $builder->arrayImmutableAppend($path, $builder->var($indexVariableName));
        $itemMapper = $this->itemMapperCompiler->compile($itemValue, $itemPath, $builder);

        $isArray = $builder->funcCall($builder->importFunction('is_array'), [$value]);
        $isList = $builder->funcCall($builder->importFunction('array_is_list'), [$value]);

        $statements = [
            $builder->if($builder->or($builder->not($isArray), $builder->not($isList)), [
                $builder->throw(
                    $builder->staticCall(
                        $builder->importClass(MappingFailedException::class),
                        'incorrectType',
                        [$value, $path, $builder->val('list')],
                    ),
                ),
            ]),

            $builder->assign($builder->var($mappedVariableName), $builder->val([])),

            $builder->foreach($value, $builder->var($itemVariableName), $builder->var($indexVariableName), [
                ...$itemMapper->statements,
                $builder->assign($builder->arrayDimFetch($builder->var($mappedVariableName)), $itemMapper->expr),
            ]),
        ];

        return new CompiledExpr($builder->var($mappedVariableName), $statements);
    }

    public function getInputType(PhpCodeBuilder $builder): TypeNode
    {
        return new IdentifierTypeNode('mixed');
    }

    public function getOutputType(PhpCodeBuilder $builder): TypeNode
    {
        $itemType = $this->itemMapperCompiler->getOutputType($builder);
        return new GenericTypeNode(new IdentifierTypeNode('list'), [$itemType]);
    }

}
