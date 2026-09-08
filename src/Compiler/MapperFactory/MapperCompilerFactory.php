<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\MapperFactory;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompilerProvider;

interface MapperCompilerFactory
{

    public const DELEGATE_OBJECT_MAPPING = 'delegateObjectMapping';
    public const GENERIC_PARAMETERS = 'genericParameters';

    /**
     * @param array<string, mixed> $options
     */
    public function create(
        TypeNode $type,
        array $options = [],
    ): MapperCompilerProvider;

}
