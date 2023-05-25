<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Validator\Float;

use Attribute;
use Nette\Utils\Floats;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\Div;
use PhpParser\Node\Stmt;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Compiler\Validator\ValidatorCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;

#[Attribute(Attribute::TARGET_PARAMETER)]
class AssertFloatMultipleOf implements ValidatorCompiler
{

    public function __construct(
        public readonly float $value,
    )
    {
    }

    /**
     * @return list<Stmt>
     */
    public function compile(
        Expr $value,
        Expr $path,
        PhpCodeBuilder $builder,
    ): array
    {
        $isFloat = $builder->funcCall($builder->importFunction('is_float'), [$value]);

        $isMultipleOf = $builder->staticCall($builder->importClass(Floats::class), 'isInteger', [
            new Div($value, $builder->val($this->value)),
        ]);

        return [
            $builder->if($builder->and($isFloat, $builder->not($isMultipleOf)), [
                $builder->throw(
                    $builder->staticCall(
                        $builder->importClass(MappingFailedException::class),
                        'incorrectValue',
                        [$value, $path, $builder->val("multiple of {$this->value}")],
                    ),
                ),
            ]),
        ];
    }

}
