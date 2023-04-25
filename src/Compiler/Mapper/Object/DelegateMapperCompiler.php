<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper\Object;

use PhpParser\Node\Expr;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\CompiledExpr;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;

class DelegateMapperCompiler implements MapperCompiler
{

    /**
     * @param class-string $className
     */
    public function __construct(
        public readonly string $className,
    )
    {
    }

    public function compile(Expr $value, Expr $path, PhpCodeBuilder $builder): CompiledExpr
    {
        $shortName = $builder->importClass($this->className);
        $provider = $builder->propertyFetch($builder->var('this'), 'provider');
        $mapper = $builder->methodCall($provider, 'get', [$builder->classConstFetch($shortName, 'class')]);
        $mapped = $builder->methodCall($mapper, 'map', [$value, $path]);

        return new CompiledExpr($mapped);
    }

    public function getInputType(PhpCodeBuilder $builder): TypeNode
    {
        return new IdentifierTypeNode('mixed');
    }

    public function getOutputType(PhpCodeBuilder $builder): TypeNode
    {
        return new IdentifierTypeNode($builder->importClass($this->className));
    }

}
