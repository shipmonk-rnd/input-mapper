<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Validator\Int;

use Attribute;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class AssertNegativeInt extends AssertIntRange
{

    public function __construct()
    {
        parent::__construct(
            lt: 0,
        );
    }

    public function getNarrowedInputType(): TypeNode
    {
        return new IdentifierTypeNode('negative-int');
    }

}
