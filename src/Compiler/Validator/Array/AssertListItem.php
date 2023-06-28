<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Validator\Array;

use Attribute;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Compiler\Validator\ValidatorCompiler;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class AssertListItem implements ValidatorCompiler
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
    public function compile(Expr $value, Expr $path, PhpCodeBuilder $builder): array
    {
        [$itemVariableName, $indexVariableName] = $builder->uniqVariableNames('item', 'index');
        $foreachBody = [];

        foreach ($this->validators as $validator) {
            $itemValue = $builder->var($itemVariableName);
            $itemPath = $builder->arrayImmutableAppend($path, $builder->var($indexVariableName));

            foreach ($validator->compile($itemValue, $itemPath, $builder) as $statement) {
                $foreachBody[] = $statement;
            }
        }

        $isArray = $builder->funcCall($builder->importFunction('is_array'), [$value]);
        $isList = $builder->funcCall($builder->importFunction('array_is_list'), [$value]);

        return [
            $builder->if($builder->and($isArray, $isList), [
                $builder->foreach(
                    $value,
                    $builder->var($itemVariableName),
                    $builder->var($indexVariableName),
                    $foreachBody,
                ),
            ]),
        ];
    }

}
