<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper;

use PhpParser\Node\Expr;
use ShipMonk\InputMapper\Compiler\CompiledExpr;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;

interface UndefinedAwareMapperCompiler extends MapperCompiler
{

    public function compileUndefined(Expr $path, Expr $key, PhpCodeBuilder $builder): CompiledExpr;

}
