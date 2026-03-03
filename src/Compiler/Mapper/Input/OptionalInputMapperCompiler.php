<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper\Input;

use PhpParser\Node\Expr;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\CompiledExpr;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\UndefinedAwareMapperCompiler;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Runtime\Optional;
use ShipMonk\InputMapper\Runtime\OptionalNone;
use ShipMonk\InputMapper\Runtime\OptionalSome;

class OptionalInputMapperCompiler implements UndefinedAwareMapperCompiler
{

    public function __construct(
        public readonly MapperCompiler $mapperCompiler,
    )
    {
    }

    public function compile(
        Expr $value,
        Expr $path,
        PhpCodeBuilder $builder,
    ): CompiledExpr
    {
        $mapper = $this->mapperCompiler->compile($value, $path, $builder);
        $mapped = $builder->staticCall($builder->importClass(Optional::class), 'of', [$mapper->expr]);
        return new CompiledExpr($mapped, $mapper->statements);
    }

    public function compileUndefined(
        Expr $path,
        Expr $key,
        PhpCodeBuilder $builder,
    ): CompiledExpr
    {
        $mapped = $builder->staticCall($builder->importClass(Optional::class), 'none', [$path, $key]);
        return new CompiledExpr($mapped);
    }

    public function getInputType(): TypeNode
    {
        return $this->mapperCompiler->getInputType();
    }

    public function getOutputType(): TypeNode
    {
        return new GenericTypeNode(
            new IdentifierTypeNode(OptionalSome::class),
            [$this->mapperCompiler->getOutputType()],
        );
    }

    public function getDefaultValueType(): TypeNode
    {
        return new IdentifierTypeNode(OptionalNone::class);
    }

}
