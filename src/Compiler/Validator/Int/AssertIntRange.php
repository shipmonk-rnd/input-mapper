<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Validator\Int;

use Attribute;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprIntegerNode;
use PHPStan\PhpDocParser\Ast\Type\ConstTypeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Compiler\Validator\NarrowingValidatorCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use function max;
use function min;
use const PHP_INT_MAX;
use const PHP_INT_MIN;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class AssertIntRange implements NarrowingValidatorCompiler
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
        TypeNode $type,
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

        return $statements;
    }

    public function getInputType(): TypeNode
    {
        return new IdentifierTypeNode('int');
    }

    public function getNarrowedInputType(): TypeNode
    {
        $inclusiveLowerBounds = [PHP_INT_MIN];
        $inclusiveUpperBounds = [PHP_INT_MAX];

        if ($this->gte !== null) {
            $inclusiveLowerBounds[] = $this->gte;
        }

        if ($this->gt !== null) {
            $inclusiveLowerBounds[] = $this->gt + 1;
        }

        if ($this->lt !== null) {
            $inclusiveUpperBounds[] = $this->lt - 1;
        }

        if ($this->lte !== null) {
            $inclusiveUpperBounds[] = $this->lte;
        }

        $inclusiveLowerBound = max($inclusiveLowerBounds);
        $inclusiveUpperBound = min($inclusiveUpperBounds);

        if ($inclusiveLowerBound === PHP_INT_MIN && $inclusiveUpperBound === PHP_INT_MAX) {
            return new IdentifierTypeNode('int');
        }

        $inclusiveLowerBoundType = $inclusiveLowerBound !== PHP_INT_MIN
            ? new ConstTypeNode(new ConstExprIntegerNode((string) $inclusiveLowerBound))
            : new IdentifierTypeNode('min');

        $inclusiveUpperBoundType = $inclusiveUpperBound !== PHP_INT_MAX
            ? new ConstTypeNode(new ConstExprIntegerNode((string) $inclusiveUpperBound))
            : new IdentifierTypeNode('max');

        return new GenericTypeNode(new IdentifierTypeNode('int'), [
            $inclusiveLowerBoundType,
            $inclusiveUpperBoundType,
        ]);
    }

}
