<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Type;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use function trim;

class GenericTypeParameter
{

    public function __construct(
        public readonly string $name,
        public readonly GenericTypeVariance $variance = GenericTypeVariance::Invariant,
        public readonly ?TypeNode $bound = null,
        public readonly ?TypeNode $default = null,
    )
    {
    }

    public function toPhpDocLine(): string
    {
        $tagName = match ($this->variance) {
            GenericTypeVariance::Invariant => 'template',
            GenericTypeVariance::Covariant => 'template-covariant',
            GenericTypeVariance::Contravariant => 'template-contravariant',
        };

        $bound = $this->bound !== null ? " of {$this->bound}" : '';
        $default = $this->default !== null ? " = {$this->default}" : '';
        return trim("@{$tagName} {$this->name}{$bound}{$default}");
    }

}
