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
use ShipMonk\InputMapper\Compiler\Type\PhpDocTypeUtils;
use ShipMonk\InputMapper\Runtime\MappingFailedException;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class MapArray implements MapperCompiler
{

    public function __construct(
        public readonly MapperCompiler $keyMapperCompiler,
        public readonly MapperCompiler $valueMapperCompiler,
    )
    {
    }

    public function compile(Expr $value, Expr $path, PhpCodeBuilder $builder): CompiledExpr
    {
        [$keyVariableName, $valueVariableName, $mappedVariableName] = $builder->uniqVariableNames('key', 'value', 'mapped');

        $itemPath = $builder->arrayImmutableAppend($path, $builder->var($keyVariableName));
        $itemKeyMapper = $this->keyMapperCompiler->compile($builder->var($keyVariableName), $itemPath, $builder);
        $itemValueMapper = $this->valueMapperCompiler->compile($builder->var($valueVariableName), $itemPath, $builder);

        $statements = [
            $builder->if($builder->not($builder->funcCall($builder->importFunction('is_array'), [$value])), [
                $builder->throw(
                    $builder->staticCall(
                        $builder->importClass(MappingFailedException::class),
                        'incorrectType',
                        [$value, $path, $builder->val('array')],
                    ),
                ),
            ]),

            $builder->assign($builder->var($mappedVariableName), $builder->val([])),

            $builder->foreach($value, $builder->var($valueVariableName), $builder->var($keyVariableName), [
                ...$itemKeyMapper->statements,
                ...$itemValueMapper->statements,
                $builder->assign($builder->arrayDimFetch($builder->var($mappedVariableName), $itemKeyMapper->expr), $itemValueMapper->expr),
            ]),
        ];

        return new CompiledExpr($builder->var($mappedVariableName), $statements);
    }

    /**
     * @return array<string, mixed>
     */
    public function getJsonSchema(): array
    {
        return ['type' => 'object', 'additionalProperties' => $this->valueMapperCompiler->getJsonSchema()];
    }

    public function getInputType(PhpCodeBuilder $builder): TypeNode
    {
        return new IdentifierTypeNode('mixed');
    }

    public function getOutputType(PhpCodeBuilder $builder): TypeNode
    {
        $keyType = $this->keyMapperCompiler->getOutputType($builder);
        $valueType = $this->valueMapperCompiler->getOutputType($builder);
        $args = PhpDocTypeUtils::isMixed($keyType) ? [$valueType] : [$keyType, $valueType];
        return new GenericTypeNode(new IdentifierTypeNode('array'), $args);
    }

}
