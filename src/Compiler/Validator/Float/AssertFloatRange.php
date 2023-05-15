<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Validator\Float;

use Attribute;
use Nette\Utils\Floats;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Compiler\Validator\ValidatorCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use function count;

#[Attribute(Attribute::TARGET_PARAMETER)]
class AssertFloatRange implements ValidatorCompiler
{

    public function __construct(
        public readonly ?float $gte = null,
        public readonly ?float $gt = null,
        public readonly ?float $lt = null,
        public readonly ?float $lte = null,
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
            $statements[] = $builder->if($builder->staticCall($builder->importClass(Floats::class), 'isLessThan', [$value, $this->gte]), [
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
            $statements[] = $builder->if($builder->staticCall($builder->importClass(Floats::class), 'isLessThanOrEqualTo', [$value, $this->gt]), [
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
            $statements[] = $builder->if($builder->staticCall($builder->importClass(Floats::class), 'isGreaterThanOrEqualTo', [$value, $this->lt]), [
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
            $statements[] = $builder->if($builder->staticCall($builder->importClass(Floats::class), 'isGreaterThan', [$value, $this->lte]), [
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
            $isFloat = $builder->funcCall($builder->importFunction('is_float'), [$value]);
            $statements = [$builder->if($isFloat, $statements)];
        }

        return $statements;
    }

}
