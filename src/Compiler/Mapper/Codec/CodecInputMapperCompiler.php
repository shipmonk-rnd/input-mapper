<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper\Codec;

use PhpParser\Node\Expr;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\CompiledExpr;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Runtime\Codec;

class CodecInputMapperCompiler implements MapperCompiler
{

    /**
     * @param class-string<Codec<*, *, *, *>> $codecClassName
     * @param class-string $domainClassName
     */
    public function __construct(
        public readonly string $codecClassName,
        public readonly MapperCompiler $intermediateMapperCompiler,
        public readonly string $domainClassName,
    )
    {
    }

    public function compile(
        Expr $value,
        Expr $path,
        PhpCodeBuilder $builder,
    ): CompiledExpr
    {
        $intermediate = $this->intermediateMapperCompiler->compile($value, $path, $builder);

        $provider = $builder->propertyFetch($builder->var('this'), 'provider');
        $codecClassExpr = $builder->classConstFetch($builder->importClass($this->codecClassName), 'class');
        $domainClassExpr = $builder->classConstFetch($builder->importClass($this->domainClassName), 'class');
        $codec = $builder->methodCall($provider, 'getCodec', [$codecClassExpr, $domainClassExpr]);
        $decoded = $builder->methodCall($codec, 'decode', [$intermediate->expr, $path]);

        return new CompiledExpr($decoded, $intermediate->statements);
    }

    public function getInputType(): TypeNode
    {
        return $this->intermediateMapperCompiler->getInputType();
    }

    public function getOutputType(): TypeNode
    {
        return new IdentifierTypeNode($this->domainClassName);
    }

}
