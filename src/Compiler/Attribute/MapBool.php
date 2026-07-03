<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Attribute;

use Attribute;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use ShipMonk\InputMapper\Compiler\Mapper\Input\BoolInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompilerProvider;
use ShipMonk\InputMapper\Compiler\MapperFactory\MapperCompilerFactory;
use ShipMonk\InputMapper\Compiler\Mapper\PassthroughMapperCompiler;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class MapBool implements MapperCompilerProvider
{

    public function getInputMapperCompiler(
        MapperCompilerFactory $mapperCompilerFactory,
        array $options,
    ): MapperCompiler
    {
        return new BoolInputMapperCompiler();
    }

    public function getOutputMapperCompiler(
        MapperCompilerFactory $mapperCompilerFactory,
        array $options,
    ): MapperCompiler
    {
        return new PassthroughMapperCompiler(new IdentifierTypeNode('bool'));
    }

}
