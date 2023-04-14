<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Exception;

use LogicException;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use Throwable;

class CannotInferMapperException extends LogicException
{

    public static function fromType(TypeNode $type, ?Throwable $previous = null): self
    {
        return new self("Cannot infer mapper from type {$type}", 0, $previous);
    }

}
