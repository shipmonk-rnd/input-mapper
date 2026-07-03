<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper\Codec;

use PhpParser\Node\Expr;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\CompiledExpr;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Runtime\Codec;

class InlineCodecOutputMapperCompiler extends AbstractInlineCodecMapperCompiler
{

    /**
     * @param class-string<Codec<*, *, *, *>> $codecClassName
     * @param array<string, scalar|null> $constructorArgs
     */
    public function __construct(
        string $codecClassName,
        array $constructorArgs,
        MapperCompiler $intermediateMapperCompiler,
        public readonly TypeNode $domainInputType,
    )
    {
        parent::__construct($codecClassName, $constructorArgs, $intermediateMapperCompiler);
    }

    public function compile(
        Expr $value,
        Expr $path,
        PhpCodeBuilder $builder,
    ): CompiledExpr
    {
        $codec = $this->compileCodecAccess($builder);

        $encodedVarName = $builder->uniqVariableName('encoded');
        $encodeCall = $builder->methodCall($codec->expr, 'encode', [$value, $path]);
        $statements = [
            ...$codec->statements,
            $builder->assign($builder->var($encodedVarName), $encodeCall),
        ];

        $outputCompiled = $this->intermediateMapperCompiler->compile($builder->var($encodedVarName), $path, $builder);

        return new CompiledExpr($outputCompiled->expr, [...$statements, ...$outputCompiled->statements]);
    }

    public function getInputType(): TypeNode
    {
        return $this->domainInputType;
    }

    public function getOutputType(): TypeNode
    {
        return $this->intermediateMapperCompiler->getOutputType();
    }

}
