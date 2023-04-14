<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper\Scalar;

use Attribute;
use PhpParser\Node\Expr;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\CompiledExpr;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Runtime\MappingFailedException;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class MapInt implements MapperCompiler
{

    public function compile(Expr $value, Expr $path, PhpCodeBuilder $builder): CompiledExpr
    {
        $statements = [
            $builder->if($builder->not($builder->funcCall($builder->importFunction('is_int'), [$value])), [
                $builder->throwNew($builder->importClass(MappingFailedException::class), [
                    $value,
                    $path,
                    $builder->val('int'),
                ]),
            ]),
        ];

        return new CompiledExpr($value, $statements);
    }

    /**
     * @return array<string, mixed>
     */
    public function getJsonSchema(): array
    {
        return ['type' => 'integer'];
    }

    public function getInputType(PhpCodeBuilder $builder): TypeNode
    {
        return new IdentifierTypeNode('mixed');
    }

    public function getOutputType(PhpCodeBuilder $builder): TypeNode
    {
        return new IdentifierTypeNode('int');
    }

}
