<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Validator\String;

use Attribute;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Compiler\Validator\ValidatorCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;

#[Attribute(Attribute::TARGET_PARAMETER)]
class AssertStringMatches implements ValidatorCompiler
{

    public function __construct(
        public readonly string $pattern,
        public readonly ?string $expectedDescription = null,
    )
    {
    }

    /**
     * @return list<Stmt>
     */
    public function compile(
        Expr $value,
        Expr $path,
        PhpCodeBuilder $builder,
    ): array
    {
        $isString = $builder->funcCall($builder->importFunction('is_string'), [$value]);
        $matchCount = $builder->funcCall($builder->importFunction('preg_match'), [$builder->val($this->pattern), $value]);

        return [
            $builder->if($builder->and($isString, $builder->notSame($matchCount, $builder->val(1))), [
                $builder->throw(
                    $builder->staticCall(
                        $builder->importClass(MappingFailedException::class),
                        'incorrectValue',
                        [$value, $path, $builder->val($this->expectedDescription ?? "string matching pattern {$this->pattern}")],
                    ),
                ),
            ]),
        ];
    }

}
