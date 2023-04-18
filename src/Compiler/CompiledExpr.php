<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler;

use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;

class CompiledExpr
{

    /**
     * @param list<Stmt> $statements list of statements to be executed before the expression
     */
    public function __construct(
        public readonly Expr $expr,
        public readonly array $statements = [],
    )
    {
    }

}
