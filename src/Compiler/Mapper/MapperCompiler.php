<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper;

use PhpParser\Node\Expr;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\CompiledExpr;
use ShipMonk\InputMapper\Compiler\Exception\CannotCompileMapperException;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;

interface MapperCompiler
{

    /**
     * @throws CannotCompileMapperException
     */
    public function compile(Expr $value, Expr $path, PhpCodeBuilder $builder): CompiledExpr;

    public function getInputType(): TypeNode;

    public function getOutputType(): TypeNode;

}
