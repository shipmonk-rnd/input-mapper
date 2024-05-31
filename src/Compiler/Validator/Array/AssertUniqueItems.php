<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Validator\Array;

use Attribute;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Compiler\Validator\ValidatorCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class AssertUniqueItems implements ValidatorCompiler
{

    /**
     * @return list<Stmt>
     */
    public function compile(Expr $value, TypeNode $type, Expr $path, PhpCodeBuilder $builder): array
    {
        [$indexVariableName, $itemVariableName, $innerLoopIndexVariableName] = $builder->uniqVariableNames(
            'index',
            'item',
            'innerIndex',
            'innerLoopItem',
        );

        $statements = [];

        $length = $builder->funcCall($builder->importFunction('count'), [$value]);

        $statements[] = $builder->foreach($value, $builder->var($itemVariableName), $builder->var($indexVariableName), [
            $builder->for(
                $builder->assignExpr(
                    $builder->var($innerLoopIndexVariableName),
                    $builder->plus($builder->var($indexVariableName), $builder->val(1)),
                ),
                $builder->lt($builder->var($innerLoopIndexVariableName), $length),
                $builder->preIncrement($builder->var($innerLoopIndexVariableName)),
                [
                    $builder->if(
                        $builder->same(
                            $builder->var($itemVariableName),
                            $builder->arrayDimFetch($value, $builder->var($innerLoopIndexVariableName)),
                        ),
                        [
                            $builder->throw(
                                $builder->staticCall(
                                    $builder->importClass(MappingFailedException::class),
                                    'duplicateValue',
                                    [
                                        $builder->var($itemVariableName),
                                        $path,
                                        $builder->val('list with unique items'),
                                    ],
                                ),
                            ),
                        ],
                    ),
                ],
            ),
        ]);

        return $statements;
    }

    public function getInputType(): TypeNode
    {
        return new IdentifierTypeNode('list');
    }

}
