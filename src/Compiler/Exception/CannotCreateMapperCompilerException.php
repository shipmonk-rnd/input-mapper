<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Exception;

use LogicException;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use Throwable;

class CannotCreateMapperCompilerException extends LogicException
{

    public static function fromType(TypeNode $type, ?string $reason = null, ?Throwable $previous = null): self
    {
        $reason = $reason !== null ? ", because {$reason}" : '';
        return new self("Cannot create mapper for type {$type}{$reason}", 0, $previous);
    }

}
