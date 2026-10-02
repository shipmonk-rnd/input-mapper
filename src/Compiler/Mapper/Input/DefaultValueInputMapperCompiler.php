<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper\Input;

use PhpParser\Node\Expr;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\CompiledExpr;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\UndefinedAwareMapperCompiler;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Compiler\Type\PhpDocTypeUtils;

class DefaultValueInputMapperCompiler implements UndefinedAwareMapperCompiler
{

    public function __construct(
        public readonly MapperCompiler $mapperCompiler,
        public readonly mixed $defaultValue,
    )
    {
    }

    public function compile(
        Expr $value,
        Expr $path,
        PhpCodeBuilder $builder,
    ): CompiledExpr
    {
        return $this->mapperCompiler->compile($value, $path, $builder);
    }

    public function compileUndefined(
        Expr $path,
        Expr $key,
        PhpCodeBuilder $builder,
    ): CompiledExpr
    {
        return new CompiledExpr($builder->val($this->defaultValue));
    }

    public function getInputType(): TypeNode
    {
        return $this->mapperCompiler->getInputType();
    }

    public function getOutputType(): TypeNode
    {
        return $this->mapperCompiler->getOutputType();
    }

    public function getDefaultValueType(): TypeNode
    {
        return PhpDocTypeUtils::fromValue($this->defaultValue);
    }

}
