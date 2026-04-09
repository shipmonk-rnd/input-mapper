<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper\Input;

use PhpParser\Builder\Method;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\Mapper\AbstractDelegateMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;

class DelegateInputMapperCompiler extends AbstractDelegateMapperCompiler
{

    public function getInputType(): TypeNode
    {
        return new IdentifierTypeNode('mixed');
    }

    public function getOutputType(): TypeNode
    {
        return $this->getClassType(static fn (MapperCompiler $mc): TypeNode => $mc->getOutputType());
    }

    protected function getProviderMethodName(): string
    {
        return 'getInputMapper';
    }

    protected function buildMapperMethod(
        string $methodName,
        MapperCompiler $mapperCompiler,
        PhpCodeBuilder $builder,
    ): Method
    {
        return $builder->inputMapperMethod($methodName, $mapperCompiler);
    }

}
