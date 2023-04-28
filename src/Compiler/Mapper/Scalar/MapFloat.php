<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper\Scalar;

use Attribute;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\CompiledExpr;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Runtime\MappingFailedException;

#[Attribute(Attribute::TARGET_PARAMETER)]
class MapFloat implements MapperCompiler
{

    /**
     * @see https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Number/MIN_SAFE_INTEGER
     */
    final public const MIN_SAFE_INTEGER = -9_007_199_254_740_991;

    /**
     * @see https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Number/MAX_SAFE_INTEGER
     */
    final public const MAX_SAFE_INTEGER = +9_007_199_254_740_991;

    public function __construct(
        public readonly bool $allowInfinity = false,
        public readonly bool $allowNan = false,
    )
    {
    }

    public function compile(Expr $value, Expr $path, PhpCodeBuilder $builder): CompiledExpr
    {
        $mappedVariableName = $builder->uniqVariableName('mapped');

        $isFloat = $builder->funcCall($builder->importFunction('is_float'), [$value]);
        $isInt = $builder->funcCall($builder->importFunction('is_int'), [$value]);

        $statements = [
            $builder->if(
                if: $isFloat,
                then: [
                    ...$this->createFiniteCheckStatements($value, $path, $builder),
                    $builder->assign($builder->var($mappedVariableName), $value),
                ],
                else: [
                    $builder->if(
                        if: $isInt,
                        then: [
                            ...$this->createSafeIntCheckStatements($value, $path, $builder),
                            $builder->assign($builder->var($mappedVariableName), $builder->funcCall($builder->importFunction('floatval'), [$value])),
                        ],
                        else: [
                            $builder->throw(
                                $builder->staticCall(
                                    $builder->importClass(MappingFailedException::class),
                                    'incorrectType',
                                    [$value, $path, 'float'],
                                ),
                            ),
                        ],
                    ),
                ],
            ),
        ];

        return new CompiledExpr($builder->var($mappedVariableName), $statements);
    }

    public function getInputType(PhpCodeBuilder $builder): TypeNode
    {
        return new IdentifierTypeNode('mixed');
    }

    public function getOutputType(PhpCodeBuilder $builder): TypeNode
    {
        return new IdentifierTypeNode('float');
    }

    /**
     * @return list<Stmt>
     */
    private function createFiniteCheckStatements(Expr $value, Expr $path, PhpCodeBuilder $builder): array
    {
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
            return [];
        }

        return [
            $builder->if($finiteCheck, [
                $builder->throw(
                    $builder->staticCall(
                        $builder->importClass(MappingFailedException::class),
                        'incorrectType',
                        [$value, $path, $finiteLabel],
                    ),
                ),
            ]),
        ];
    }

    /**
     * @return list<Stmt>
     */
    private function createSafeIntCheckStatements(Expr $value, Expr $path, PhpCodeBuilder $builder): array
    {
        $isUnsafeInt = $builder->or(
            $builder->lt($value, $builder->val(self::MIN_SAFE_INTEGER)),
            $builder->gt($value, $builder->val(self::MAX_SAFE_INTEGER)),
        );

        return [
            $builder->if($isUnsafeInt, [
                $builder->throw(
                    $builder->staticCall(
                        $builder->importClass(MappingFailedException::class),
                        'incorrectValue',
                        [$value, $path, 'float or int with value that can be losslessly converted to float'],
                    ),
                ),
            ]),
        ];
    }

}
