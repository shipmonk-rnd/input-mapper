<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Validator;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;

interface NarrowingValidatorCompiler extends ValidatorCompiler
{

    /**
     * Must return subtype of input type returned by {@link self::getInputType()}.
     */
    public function getNarrowedInputType(): TypeNode;

}
