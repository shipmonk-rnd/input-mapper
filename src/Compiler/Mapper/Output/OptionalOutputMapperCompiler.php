<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper\Output;

use PhpParser\Node\Expr;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\CompiledExpr;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Runtime\Optional;

class OptionalOutputMapperCompiler implements MapperCompiler
{

    public function __construct(
        public readonly MapperCompiler $innerMapperCompiler,
    )
    {
    }

    public function compile(
        Expr $value,
        Expr $path,
        PhpCodeBuilder $builder,
    ): CompiledExpr
    {
        $innerValue = $builder->methodCall($value, 'get');
        return $this->innerMapperCompiler->compile($innerValue, $path, $builder);
    }

    public function getInputType(): TypeNode
    {
        return new GenericTypeNode(
            new IdentifierTypeNode(Optional::class),
            [$this->innerMapperCompiler->getInputType()],
        );
    }

    public function getOutputType(): TypeNode
    {
        return $this->innerMapperCompiler->getOutputType();
    }

}
