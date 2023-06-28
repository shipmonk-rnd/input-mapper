<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Validator\String;

use Attribute;
use LogicException;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Compiler\Validator\ValidatorCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use function count;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class AssertStringLength implements ValidatorCompiler
{

    public readonly ?int $min;

    public readonly ?int $max;

    public function __construct(
        ?int $exact = null,
        ?int $min = null,
        ?int $max = null,
    )
    {
        if ($exact !== null && ($min !== null || $max !== null)) {
            throw new LogicException('Cannot use "exact" and "min" or "max" at the same time');
        }

        $this->min = $exact ?? $min;
        $this->max = $exact ?? $max;
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
        $statements = [];
        $length = $builder->funcCall($builder->importFunction('strlen'), [$value]);

        if ($this->min !== null && $this->max !== null && $this->min === $this->max) {
            $statements[] = $builder->if($builder->notSame($length, $builder->val($this->min)), [
                $builder->throw(
                    $builder->staticCall(
                        $builder->importClass(MappingFailedException::class),
                        'incorrectValue',
                        [$value, $path, $builder->val("string with exactly {$this->min} characters")],
                    ),
                ),
            ]);

        } else {
            if ($this->min !== null) {
                $statements[] = $builder->if($builder->lt($length, $builder->val($this->min)), [
                    $builder->throw(
                        $builder->staticCall(
                            $builder->importClass(MappingFailedException::class),
                            'incorrectValue',
                            [$value, $path, $builder->val("string with at least {$this->min} characters")],
                        ),
                    ),
                ]);
            }

            if ($this->max !== null) {
                $statements[] = $builder->if($builder->gt($length, $builder->val($this->max)), [
                    $builder->throw(
                        $builder->staticCall(
                            $builder->importClass(MappingFailedException::class),
                            'incorrectValue',
                            [$value, $path, $builder->val("string with at most {$this->max} characters")],
                        ),
                    ),
                ]);
            }
        }

        if (count($statements) > 0) {
            $isString = $builder->funcCall($builder->importFunction('is_string'), [$value]);
            $statements = [$builder->if($isString, $statements)];
        }

        return $statements;
    }

}
