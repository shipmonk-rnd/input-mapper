<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper\Mixed;

use Attribute;
use PhpParser\Node\Expr;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\CompiledExpr;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class MapMixed implements MapperCompiler
{

    public function compile(Expr $value, Expr $path, PhpCodeBuilder $builder): CompiledExpr
    {
        return new CompiledExpr($value);
    }

    /**
     * @return array<string, mixed>
     */
    public function getJsonSchema(): array
    {
        return [];
    }

    public function getInputType(PhpCodeBuilder $builder): TypeNode
    {
        return new IdentifierTypeNode('mixed');
    }

    public function getOutputType(PhpCodeBuilder $builder): TypeNode
    {
        return new IdentifierTypeNode('mixed');
    }

}
