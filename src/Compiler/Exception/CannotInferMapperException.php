<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Exception;

use LogicException;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use Throwable;

class CannotInferMapperException extends LogicException
{

    public static function fromType(TypeNode $type, ?string $reason = null, ?Throwable $previous = null): self
    {
        $reason = $reason ? ", because {$reason}" : '';
        return new self("Cannot infer mapper for type {$type}{$reason}", 0, $previous);
    }

}
