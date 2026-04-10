<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper\Output;

use PhpParser\Builder\Method;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\Mapper\AbstractDelegateMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;

class DelegateOutputMapperCompiler extends AbstractDelegateMapperCompiler
{

    public function getInputType(): TypeNode
    {
        return $this->getClassType(static fn (MapperCompiler $mc): TypeNode => $mc->getInputType());
    }

    public function getOutputType(): TypeNode
    {
        return new IdentifierTypeNode('mixed'); // exact type unknown because the delegate mapper is resolved at runtime
    }

    protected function getProviderMethodName(): string
    {
        return 'getOutputMapper';
    }

    protected function buildMapperMethod(
        string $methodName,
        MapperCompiler $mapperCompiler,
        PhpCodeBuilder $builder,
    ): Method
    {
        return $builder->mapperMethod($methodName, $mapperCompiler);
    }

}
