<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Validator;

use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Expression;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Runtime\MappingFailedException;

abstract class AssertRuntime implements ValidatorCompiler
{

    /**
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    abstract public static function assertValue(mixed $value, array $path): void;

    /**
     * @return list<Stmt>
     */
    public function compile(
        Expr $value,
        Expr $path,
        PhpCodeBuilder $builder,
    ): array
    {
        return [
            new Expression(
                $builder->staticCall(
                    $builder->importClass(static::class),
                    'assertValue',
                    [$value, $path],
                ),
            ),
        ];
    }

    /**
     * @param  array<string, mixed> $schema
     * @return array<string, mixed>
     */
    public function toJsonSchema(array $schema): array
    {
        return $schema;
    }

}
