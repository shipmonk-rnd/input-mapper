<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Validator\String;

use Attribute;
use Nette\Utils\Validators;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Compiler\Validator\ValidatorCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class AssertUrl implements ValidatorCompiler
{

    /**
     * @return list<Stmt>
     */
    public function compile(
        Expr $value,
        TypeNode $type,
        Expr $context,
        PhpCodeBuilder $builder,
    ): array
    {
        $isUrl = $builder->staticCall($builder->importClass(Validators::class), 'isUrl', [$value]);

        return [
            $builder->if($builder->not($isUrl), [
                $builder->throw(
                    $builder->staticCall(
                        $builder->importClass(MappingFailedException::class),
                        'incorrectValue',
                        [$value, $context, $builder->val('valid URL')],
                    ),
                ),
            ]),
        ];
    }

    public function getInputType(): TypeNode
    {
        return new IdentifierTypeNode('string');
    }

}
