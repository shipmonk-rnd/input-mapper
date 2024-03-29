<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Validator\Int;

use Attribute;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\Mod;
use PhpParser\Node\Stmt;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Compiler\Validator\ValidatorCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class AssertIntMultipleOf implements ValidatorCompiler
{

    public function __construct(
        public readonly int $value,
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
        $modulo = new Mod($value, $builder->val($this->value));

        return [
            $builder->if($builder->notSame($modulo, $builder->val(0)), [
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

    public function getInputType(): TypeNode
    {
        return new IdentifierTypeNode('int');
    }

}
