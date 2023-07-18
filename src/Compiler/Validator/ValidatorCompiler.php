<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Validator;

use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;

interface ValidatorCompiler
{

    /**
     * @return list<Stmt>
     */
    public function compile(
        Expr $value,
        Expr $path,
        PhpCodeBuilder $builder,
    ): array;

    public function getInputType(): TypeNode;

}
