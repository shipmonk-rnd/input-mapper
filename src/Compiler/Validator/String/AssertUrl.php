<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Validator\String;

use Attribute;
use Nette\Utils\Validators;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Compiler\Validator\ValidatorCompiler;
use ShipMonk\InputMapper\Runtime\MappingFailedException;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class AssertUrl implements ValidatorCompiler
{

    /**
     * @return list<Stmt>
     */
    public function compileValidator(
        Expr $value,
        Expr $path,
        PhpCodeBuilder $builder,
    ): array
    {
        $isString = $builder->funcCall($builder->importFunction('is_string'), [$value]);
        $isUrl = $builder->staticCall($builder->importClass(Validators::class), 'isUrl', [$value]);

        return [
            $builder->if($builder->and($isString, $builder->not($isUrl)), [
                $builder->throwNew($builder->importClass(MappingFailedException::class), [
                    $value,
                    $path,
                    $builder->val('valid URL'),
                ]),
            ]),
        ];
    }

    /**
     * @param  array<string, mixed> $schema
     * @return array<string, mixed>
     */
    public function toJsonSchema(array $schema): array
    {
        $schema['format'] = 'uri';
        return $schema;
    }

}
