<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler;

use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;

class CompiledExpr
{

    /**
     * @param list<Stmt> $statements
     */
    public function __construct(
        public readonly Expr $expr,
        public readonly array $statements = [],
    )
    {
    }

}
