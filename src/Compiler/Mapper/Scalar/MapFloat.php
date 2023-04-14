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
class MapFloat implements MapperCompiler
{

    public function compile(Expr $value, Expr $path, PhpCodeBuilder $builder): CompiledExpr
    {
        $isFloat = $builder->funcCall($builder->importFunction('is_float'), [$value]);
        $isInt = $builder->funcCall($builder->importFunction('is_int'), [$value]);

        $statements = [
            $builder->if($builder->and($builder->not($isFloat), $builder->not($isInt)), [
                $builder->throwNew($builder->importClass(MappingFailedException::class), [
                    $value,
                    $path,
                    $builder->val('float'),
                ]),
            ]),
        ];

        return new CompiledExpr($builder->funcCall($builder->importFunction('floatval'), [$value]), $statements);
    }

    /**
     * @return array<string, mixed>
     */
    public function getJsonSchema(): array
    {
        return ['type' => 'number'];
    }

    public function getInputType(PhpCodeBuilder $builder): TypeNode
    {
        return new IdentifierTypeNode('mixed');
    }

    public function getOutputType(PhpCodeBuilder $builder): TypeNode
    {
        return new IdentifierTypeNode('float');
    }

}
