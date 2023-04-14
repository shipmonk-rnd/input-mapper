<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper;

use PhpParser\Node\Expr;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\CompiledExpr;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;

interface MapperCompiler
{

    public function compile(Expr $value, Expr $path, PhpCodeBuilder $builder): CompiledExpr;

    /**
     * @return array<string, mixed>
     */
    public function getJsonSchema(): array;

    public function getInputType(PhpCodeBuilder $builder): TypeNode;

    public function getOutputType(PhpCodeBuilder $builder): TypeNode;

}
