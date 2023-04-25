<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper\Scalar;

use Attribute;
use PhpParser\Node\Expr;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\CompiledExpr;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Runtime\MappingFailedException;

#[Attribute(Attribute::TARGET_PARAMETER)]
class MapFloat implements MapperCompiler
{

    public function __construct(
        public readonly bool $allowInfinity = false,
        public readonly bool $allowNan = false,
    )
    {
    }

    public function compile(Expr $value, Expr $path, PhpCodeBuilder $builder): CompiledExpr
    {
        $isFloat = $builder->funcCall($builder->importFunction('is_float'), [$value]);
        $isInt = $builder->funcCall($builder->importFunction('is_int'), [$value]);

        $statements = [
            $builder->if($builder->and($builder->not($isFloat), $builder->not($isInt)), [
                $builder->throw(
                    $builder->staticCall(
                        $builder->importClass(MappingFailedException::class),
                        'incorrectType',
                        [$value, $path, 'float'],
                    ),
                ),
            ]),
        ];

        if (!$this->allowInfinity && !$this->allowNan) {
            $finiteCheck = $builder->not($builder->funcCall($builder->importFunction('is_finite'), [$value]));
            $finiteLabel = 'finite float';

        } elseif (!$this->allowInfinity) {
            $finiteCheck = $builder->funcCall($builder->importFunction('is_infinite'), [$value]);
            $finiteLabel = 'finite float or NAN';

        } elseif (!$this->allowNan) {
            $finiteCheck = $builder->funcCall($builder->importFunction('is_nan'), [$value]);
            $finiteLabel = 'finite float or INF';

        } else {
            $finiteCheck = null;
            $finiteLabel = null;
        }

        if ($finiteCheck !== null) {
            $statements[] = $builder->if($finiteCheck, [
                $builder->throw(
                    $builder->staticCall(
                        $builder->importClass(MappingFailedException::class),
                        'incorrectType',
                        [$value, $path, $finiteLabel],
                    ),
                ),
            ]);
        }

        return new CompiledExpr($builder->funcCall($builder->importFunction('floatval'), [$value]), $statements);
    }

    public function getInputType(PhpCodeBuilder $builder): TypeNode
    {
        return new IdentifierTypeNode('mixed');
    }

    public function getOutputType(PhpCodeBuilder $builder): TypeNode
    {
        return new IdentifierTypeNode('float');
    }

}
