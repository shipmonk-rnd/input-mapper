<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Validator\Array;

use Attribute;
use LogicException;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Compiler\Validator\NarrowingValidatorCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class AssertListLength implements NarrowingValidatorCompiler
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
    public function compile(Expr $value, TypeNode $type, Expr $path, PhpCodeBuilder $builder): array
    {
        $statements = [];
        $length = $builder->funcCall($builder->importFunction('count'), [$value]);

        if ($this->min !== null && $this->max !== null && $this->min === $this->max) {
            $statements[] = $builder->if($builder->notSame($length, $builder->val($this->min)), [
                $builder->throw(
                    $builder->staticCall(
                        $builder->importClass(MappingFailedException::class),
                        'incorrectValue',
                        [$value, $path, $builder->val("list with exactly {$this->min} items")],
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
                            [$value, $path, $builder->val("list with at least {$this->min} items")],
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
                            [$value, $path, $builder->val("list with at most {$this->max} items")],
                        ),
                    ),
                ]);
            }
        }

        return $statements;
    }

    public function getInputType(): TypeNode
    {
        return new IdentifierTypeNode('list');
    }

    public function getNarrowedInputType(): TypeNode
    {
        $itemType = new IdentifierTypeNode('mixed');

        if ($this->min !== null && $this->min > 0) {
            return new GenericTypeNode(new IdentifierTypeNode('non-empty-list'), [$itemType]);
        }

        return new GenericTypeNode(new IdentifierTypeNode('list'), [$itemType]);
    }

}
