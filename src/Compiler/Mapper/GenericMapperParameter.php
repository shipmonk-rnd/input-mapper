<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper;

use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\Type\GenericTypeParameter;
use ShipMonk\InputMapper\Compiler\Type\GenericTypeVariance;

class GenericMapperParameter
{

    public readonly string $name;

    public readonly GenericTypeVariance $variance;

    public readonly ?TypeNode $bound;

    public readonly ?TypeNode $default;

    public function __construct(
        GenericTypeParameter $typeParameter,
        public readonly TypeNode $innerMapperInputType,
        public readonly TypeNode $innerMapperOutputType,
    )
    {
        $this->name = $typeParameter->name;
        $this->variance = $typeParameter->variance;
        $this->bound = $typeParameter->bound;
        $this->default = $typeParameter->default;
    }

    public static function input(GenericTypeParameter $typeParameter): self
    {
        return new self(
            $typeParameter,
            new IdentifierTypeNode('mixed'),
            new IdentifierTypeNode($typeParameter->name),
        );
    }

    public static function output(GenericTypeParameter $typeParameter): self
    {
        return new self(
            $typeParameter,
            new IdentifierTypeNode($typeParameter->name),
            new IdentifierTypeNode('mixed'),
        );
    }

}
