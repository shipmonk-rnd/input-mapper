<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Validator\String;

use Attribute;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Compiler\Validator\NarrowingValidatorCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class AssertStringNonEmpty extends AssertStringMatches implements NarrowingValidatorCompiler
{

    public function __construct()
    {
        parent::__construct(
            pattern: '#\S#',
            expectedDescription: 'non-empty string',
        );
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
        $isEmpty = $builder->same($value, $builder->val(''));
        $matchCount = $builder->funcCall($builder->importFunction('preg_match'), [$builder->val($this->pattern), $value]);
        $noMatch = $builder->notSame($matchCount, $builder->val(1));

        return [
            $builder->if($builder->or($isEmpty, $noMatch), [
                $builder->throw(
                    $builder->staticCall(
                        $builder->importClass(MappingFailedException::class),
                        'incorrectValue',
                        [$value, $path, $builder->val('non-empty string')],
                    ),
                ),
            ]),
        ];
    }

    public function getNarrowedType(): TypeNode
    {
        return new IdentifierTypeNode('non-empty-string');
    }

}
