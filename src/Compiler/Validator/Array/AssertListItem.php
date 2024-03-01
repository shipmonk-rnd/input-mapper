<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Validator\Array;

use Attribute;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Compiler\Type\PhpDocTypeUtils;
use ShipMonk\InputMapper\Compiler\Validator\NarrowingValidatorCompiler;
use ShipMonk\InputMapper\Compiler\Validator\ValidatorCompiler;
use function array_map;
use function count;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class AssertListItem implements NarrowingValidatorCompiler
{

    /**
     * @param  list<ValidatorCompiler> $validators
     */
    public function __construct(
        public readonly array $validators,
    )
    {
    }

    /**
     * @return list<Stmt>
     */
    public function compile(Expr $value, TypeNode $type, Expr $context, PhpCodeBuilder $builder): array
    {
        [$itemVariableName, $indexVariableName] = $builder->uniqVariableNames('item', 'index');
        $foreachBody = [];

        foreach ($this->validators as $validator) {
            $itemValue = $builder->var($itemVariableName);
            $itemType = PhpDocTypeUtils::inferGenericParameter($type, 'list', 0);
            $itemContext = $builder->mapperContextAppend($context, $builder->var($indexVariableName));

            foreach ($validator->compile($itemValue, $itemType, $itemContext, $builder) as $statement) {
                $foreachBody[] = $statement;
            }
        }

        if (count($foreachBody) === 0) {
            return [];
        }

        return [
            $builder->foreach(
                $value,
                $builder->var($itemVariableName),
                $builder->var($indexVariableName),
                $foreachBody,
            ),
        ];
    }

    public function getInputType(): TypeNode
    {
        $validatorInputTypes = array_map(
            static fn(ValidatorCompiler $validator) => $validator->getInputType(),
            $this->validators,
        );

        return new GenericTypeNode(
            new IdentifierTypeNode('list'),
            [PhpDocTypeUtils::intersect(...$validatorInputTypes)],
        );
    }

    public function getNarrowedInputType(): TypeNode
    {
        $validatorOutputTypes = [];

        foreach ($this->validators as $validator) {
            $validatorOutputTypes[] = $validator instanceof NarrowingValidatorCompiler
                ? $validator->getNarrowedInputType()
                : $validator->getInputType();
        }

        return new GenericTypeNode(
            new IdentifierTypeNode('list'),
            [PhpDocTypeUtils::intersect(...$validatorOutputTypes)],
        );
    }

}
