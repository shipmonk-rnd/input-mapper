<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Validator\Array;

use Attribute;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Compiler\Validator\ValidatorCompiler;
use function array_values;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class AssertListItem implements ValidatorCompiler
{

    /**
     * @var list<ValidatorCompiler>
     */
    private array $validators;

    public function __construct(
        ValidatorCompiler ...$validators,
    )
    {
        $this->validators = array_values($validators);
    }

    /**
     * @return list<Stmt>
     */
    public function compileValidator(Expr $value, Expr $path, PhpCodeBuilder $builder,): array
    {
        [$itemVariableName, $indexVariableName] = $builder->uniqVariableNames('item', 'index');
        $foreachBody = [];

        foreach ($this->validators as $validator) {
            $itemValue = $builder->var($itemVariableName);
            $itemPath = $builder->arrayImmutableAppend($path, $builder->var($indexVariableName));

            foreach ($validator->compileValidator($itemValue, $itemPath, $builder) as $statement) {
                $foreachBody[] = $statement;
            }
        }

        $isArray = $builder->funcCall($builder->importFunction('is_array'), [$value]);
        $isList = $builder->funcCall($builder->importFunction('array_is_list'), [$value]);

        return [
            $builder->if($builder->and($isArray, $isList), [], [
                $builder->foreach(
                    $value,
                    $builder->var($itemVariableName),
                    $builder->var($indexVariableName),
                    $foreachBody,
                ),
            ]),
        ];
    }

    /**
     * @param  array<string, mixed> $schema
     * @return array<string, mixed>
     */
    public function toJsonSchema(array $schema): array
    {
        foreach ($this->validators as $validator) {
            $schema = $validator->toJsonSchema($schema);
        }

        return $schema;
    }

}
