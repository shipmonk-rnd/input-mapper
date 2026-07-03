<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper\Codec;

use PhpParser\Node\Expr;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\CompiledExpr;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Runtime\Codec;

class InlineCodecInputMapperCompiler extends AbstractInlineCodecMapperCompiler
{

    /**
     * @param class-string<Codec<*, *, *, *>> $codecClassName
     * @param array<string, scalar|null> $constructorArgs
     */
    public function __construct(
        string $codecClassName,
        array $constructorArgs,
        MapperCompiler $intermediateMapperCompiler,
        public readonly TypeNode $domainOutputType,
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
        $intermediate = $this->intermediateMapperCompiler->compile($value, $path, $builder);
        $codec = $this->compileCodecAccess($builder);

        $decoded = $builder->methodCall($codec->expr, 'decode', [$intermediate->expr, $path]);

        return new CompiledExpr($decoded, [...$intermediate->statements, ...$codec->statements]);
    }

    public function getInputType(): TypeNode
    {
        return $this->intermediateMapperCompiler->getInputType();
    }

    public function getOutputType(): TypeNode
    {
        return $this->domainOutputType;
    }

}
