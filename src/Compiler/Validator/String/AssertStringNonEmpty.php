<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Validator\String;

use Attribute;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\Validator\NarrowingValidatorCompiler;

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

    public function getNarrowedInputType(): TypeNode
    {
        return new IdentifierTypeNode('non-empty-string');
    }

}
