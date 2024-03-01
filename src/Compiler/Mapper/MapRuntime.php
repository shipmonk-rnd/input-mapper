<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper;

use PhpParser\Node\Expr;
use ShipMonk\InputMapper\Compiler\CompiledExpr;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\MapperContext;

abstract class MapRuntime implements MapperCompiler
{

    /**
     * @throws MappingFailedException
     */
    abstract public static function mapValue(mixed $value, ?MapperContext $context): mixed;

    public function compile(
        Expr $value,
        Expr $context,
        PhpCodeBuilder $builder,
    ): CompiledExpr
    {
        return new CompiledExpr(
            $builder->staticCall(
                $builder->importClass(static::class),
                'mapValue',
                [$value, $context],
            ),
        );
    }

}
