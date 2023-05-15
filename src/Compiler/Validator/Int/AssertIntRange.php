<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Validator\Int;

use Attribute;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Compiler\Validator\ValidatorCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use function count;

#[Attribute(Attribute::TARGET_PARAMETER)]
class AssertIntRange implements ValidatorCompiler
{

    public function __construct(
        public readonly ?int $gte = null,
        public readonly ?int $gt = null,
        public readonly ?int $lt = null,
        public readonly ?int $lte = null,
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
        $statements = [];

        if ($this->gte !== null) {
            $statements[] = $builder->if($builder->lt($value, $builder->val($this->gte)), [
                $builder->throw(
                    $builder->staticCall(
                        $builder->importClass(MappingFailedException::class),
                        'incorrectValue',
                        [$value, $path, $builder->val("value greater than or equal to {$this->gte}")],
                    ),
                ),
            ]);
        }

        if ($this->gt !== null) {
            $statements[] = $builder->if($builder->lte($value, $builder->val($this->gt)), [
                $builder->throw(
                    $builder->staticCall(
                        $builder->importClass(MappingFailedException::class),
                        'incorrectValue',
                        [$value, $path, $builder->val("value greater than {$this->gt}")],
                    ),
                ),
            ]);
        }

        if ($this->lt !== null) {
            $statements[] = $builder->if($builder->gte($value, $builder->val($this->lt)), [
                $builder->throw(
                    $builder->staticCall(
                        $builder->importClass(MappingFailedException::class),
                        'incorrectValue',
                        [$value, $path, $builder->val("value less than {$this->lt}")],
                    ),
                ),
            ]);
        }

        if ($this->lte !== null) {
            $statements[] = $builder->if($builder->gt($value, $builder->val($this->lte)), [
                $builder->throw(
                    $builder->staticCall(
                        $builder->importClass(MappingFailedException::class),
                        'incorrectValue',
                        [$value, $path, $builder->val("value less than or equal to {$this->lte}")],
                    ),
                ),
            ]);
        }

        if (count($statements) > 0) {
            $isInt = $builder->funcCall($builder->importFunction('is_int'), [$value]);
            $statements = [$builder->if($isInt, $statements)];
        }

        return $statements;
    }

}
