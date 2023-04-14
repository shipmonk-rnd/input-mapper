<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Validator;

use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;

interface ValidatorCompiler
{

    /**
     * @return list<Stmt>
     */
    public function compileValidator(
        Expr $value,
        Expr $path,
        PhpCodeBuilder $builder,
    ): array;

    /**
     * @param  array<string, mixed> $schema
     * @return array<string, mixed>
     */
    public function toJsonSchema(array $schema): array;

}
