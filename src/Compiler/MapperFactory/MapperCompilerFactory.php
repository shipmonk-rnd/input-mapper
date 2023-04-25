<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\MapperFactory;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;

interface MapperCompilerFactory
{

    public const DELEGATE_OBJECT_MAPPING = 'delegateObjectMapping';

    /**
     * @param  array<string, mixed> $options
     */
    public function create(TypeNode $type, array $options): MapperCompiler;

}
