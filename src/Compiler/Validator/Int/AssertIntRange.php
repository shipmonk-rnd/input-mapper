<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Validator\Int;

use Attribute;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Compiler\Validator\ValidatorCompiler;
use ShipMonk\InputMapper\Runtime\MappingFailedException;
use function count;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class AssertIntRange implements ValidatorCompiler
{

    private ?int $lt;

    private ?int $lte;

    private ?int $gt;

    private ?int $gte;

    public function __construct(
        ?int $lt = null,
        ?int $lte = null,
        ?int $gt = null,
        ?int $gte = null,
    )
    {
        $this->lt = $lt;
        $this->lte = $lte;
        $this->gt = $gt;
        $this->gte = $gte;
    }

    /**
     * @return list<Stmt>
     */
    public function compileValidator(
        Expr $value,
        Expr $path,
        PhpCodeBuilder $builder,
    ): array
    {
        $statements = [];

        if ($this->lt !== null) {
            $statements[] = $builder->if($builder->gte($value, $builder->val($this->lt)), [
                $builder->throw(
                    $builder->staticCall(
                        $builder->importClass(MappingFailedException::class),
                        'incorrectValue',
                        [$value, $path, $builder->val("value smaller than {$this->lt}")],
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
                        [$value, $path, $builder->val("value smaller or equal to {$this->lte}")],
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

        if (count($statements) > 0) {
            $isInt = $builder->funcCall($builder->importFunction('is_int'), [$value]);
            $statements = [$builder->if($isInt, $statements)];
        }

        return $statements;
    }

    /**
     * @param  array<string, mixed> $schema
     * @return array<string, mixed>
     */
    public function toJsonSchema(array $schema): array
    {
        if ($this->lt !== null) {
            $schema['exclusiveMaximum'] = $this->lt;
        }

        if ($this->lte !== null) {
            $schema['maximum'] = $this->lte;
        }

        if ($this->gt !== null) {
            $schema['exclusiveMinimum'] = $this->gt;
        }

        if ($this->gte !== null) {
            $schema['minimum'] = $this->gte;
        }

        return $schema;
    }

}
