<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\MapperFactory;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;

interface MapperCompilerFactory
{

    public function create(TypeNode $type, bool $delegateObjectMapping = true): MapperCompiler;

}
