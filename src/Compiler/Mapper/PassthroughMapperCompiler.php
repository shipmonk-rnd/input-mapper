<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper;

use PhpParser\Node\Expr;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\CompiledExpr;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;

class PassthroughMapperCompiler implements MapperCompiler
{

    public function __construct(
        private readonly TypeNode $type,
    )
    {
    }

    public function compile(
        Expr $value,
        Expr $path,
        PhpCodeBuilder $builder,
    ): CompiledExpr
    {
        return new CompiledExpr($value);
    }

    public function getInputType(): TypeNode
    {
        return $this->type;
    }

    public function getOutputType(): TypeNode
    {
        return $this->type;
    }

}
