<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Validator;

use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Expression;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\MapperContext;

abstract class AssertRuntime implements ValidatorCompiler
{

    /**
     * @throws MappingFailedException
     */
    abstract public static function assertValue(mixed $value, ?MapperContext $context = null): void;

    /**
     * @return list<Stmt>
     */
    public function compile(
        Expr $value,
        TypeNode $type,
        Expr $context,
        PhpCodeBuilder $builder,
    ): array
    {
        return [
            new Expression(
                $builder->staticCall(
                    $builder->importClass(static::class),
                    'assertValue',
                    [$value, $context],
                ),
            ),
        ];
    }

    public function getInputType(): TypeNode
    {
        return new IdentifierTypeNode('mixed');
    }

}
