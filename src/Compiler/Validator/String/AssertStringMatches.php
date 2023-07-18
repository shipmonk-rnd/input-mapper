<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Validator\String;

use Attribute;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Compiler\Validator\ValidatorCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
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
        TypeNode $type,
        Expr $path,
        PhpCodeBuilder $builder,
    ): array
    {
        $matchCount = $builder->funcCall($builder->importFunction('preg_match'), [$builder->val($this->pattern), $value]);

        return [
            $builder->if($builder->notSame($matchCount, $builder->val(1)), [
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

    public function getInputType(): TypeNode
    {
        return new IdentifierTypeNode('string');
    }

}
