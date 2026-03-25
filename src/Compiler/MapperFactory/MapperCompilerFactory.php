<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\MapperFactory;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\Attribute\MapperCompilerProvider;

interface MapperCompilerFactory
{

    /**
     * @param array<string, mixed> $options
     */
    public function create(
        TypeNode $type,
        array $options = [],
    ): MapperCompilerProvider;

}
