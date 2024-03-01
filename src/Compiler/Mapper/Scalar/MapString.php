<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper\Scalar;

use Attribute;
use PhpParser\Node\Expr;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\CompiledExpr;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class MapString implements MapperCompiler
{

    public function compile(Expr $value, Expr $context, PhpCodeBuilder $builder): CompiledExpr
    {
        $statements = [
            $builder->if($builder->not($builder->funcCall($builder->importFunction('is_string'), [$value])), [
                $builder->throw(
                    $builder->staticCall(
                        $builder->importClass(MappingFailedException::class),
                        'incorrectType',
                        [$value, $context, $builder->val('string')],
                    ),
                ),
            ]),
        ];

        return new CompiledExpr($value, $statements);
    }

    public function getInputType(): TypeNode
    {
        return new IdentifierTypeNode('mixed');
    }

    public function getOutputType(): TypeNode
    {
        return new IdentifierTypeNode('string');
    }

}
