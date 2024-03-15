<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Type;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;

class GenericTypeDefinition
{

    /**
     * @param array<string, list<int | TypeNode>> $extends
     * @param list<GenericTypeParameter>          $parameters
     * @param array<int, list<?int>>              $parameterOffsetMapping indexed by [parameterCount]
     */
    public function __construct(
        public readonly array $extends = [],
        public readonly array $parameters = [],
        public readonly array $parameterOffsetMapping = [],
    )
    {
    }

}
