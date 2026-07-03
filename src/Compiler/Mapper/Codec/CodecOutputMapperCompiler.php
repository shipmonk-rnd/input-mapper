<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper\Codec;

use PhpParser\Node\Expr;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\CompiledExpr;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Runtime\Codec;

class CodecOutputMapperCompiler implements MapperCompiler
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
        $provider = $builder->propertyFetch($builder->var('this'), 'provider');
        $codecClassExpr = $builder->classConstFetch($builder->importClass($this->codecClassName), 'class');
        $domainClassExpr = $builder->classConstFetch($builder->importClass($this->domainClassName), 'class');
        $codec = $builder->methodCall($provider, 'getCodec', [$codecClassExpr, $domainClassExpr]);

        $encodedVarName = $builder->uniqVariableName('encoded');
        $encodeCall = $builder->methodCall($codec, 'encode', [$value, $path]);
        $statements = [$builder->assign($builder->var($encodedVarName), $encodeCall)];

        $outputCompiled = $this->intermediateMapperCompiler->compile($builder->var($encodedVarName), $path, $builder);

        return new CompiledExpr($outputCompiled->expr, [...$statements, ...$outputCompiled->statements]);
    }

    public function getInputType(): TypeNode
    {
        return new IdentifierTypeNode($this->domainClassName);
    }

    public function getOutputType(): TypeNode
    {
        return $this->intermediateMapperCompiler->getOutputType();
    }

}
