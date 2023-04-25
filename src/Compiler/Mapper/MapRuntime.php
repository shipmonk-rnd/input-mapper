<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper;

use PhpParser\Node\Expr;
use ShipMonk\InputMapper\Compiler\CompiledExpr;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Runtime\MappingFailedException;

abstract class MapRuntime implements MapperCompiler
{

    /**
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    abstract public static function mapValue(mixed $value, array $path): mixed;

    public function compile(
        Expr $value,
        Expr $path,
        PhpCodeBuilder $builder,
    ): CompiledExpr
    {
        return new CompiledExpr(
            $builder->staticCall(
                $builder->importClass(static::class),
                'mapValue',
                [$value, $path],
            ),
        );
    }

}
