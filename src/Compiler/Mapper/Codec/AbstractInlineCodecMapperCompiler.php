<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper\Codec;

use PhpParser\Node\Expr\AssignOp\Coalesce;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Stmt\Expression;
use ShipMonk\InputMapper\Compiler\CompiledExpr;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Runtime\Codec;
use function array_map;
use function array_values;

abstract class AbstractInlineCodecMapperCompiler implements MapperCompiler
{

    /**
     * @param class-string<Codec<*, *, *, *>> $codecClassName
     * @param array<string, scalar|null> $constructorArgs
     */
    public function __construct(
        public readonly string $codecClassName,
        public readonly array $constructorArgs,
        public readonly MapperCompiler $intermediateMapperCompiler,
    )
    {
    }

    /**
     * Registers a lazily initialized codec property on the generated class.
     * Returns a CompiledExpr whose expr is the codec property access
     * and whose statements contain the coalesce-assign: `$this->codec ??= new CodecClass(args);`
     */
    protected function compileCodecAccess(PhpCodeBuilder $builder): CompiledExpr
    {
        $codecPropertyName = $builder->uniqVariableName('codec');

        $codecShortName = $builder->importClass($this->codecClassName);
        $codecProperty = $builder->property($codecPropertyName)
            ->makePrivate()
            ->setType(new NullableType(new Name($codecShortName)))
            ->setDefault(null)
            ->getNode();
        $builder->addProperty($codecProperty);

        $argExprs = array_map($builder->val(...), array_values($this->constructorArgs));
        $newCodec = $builder->new($codecShortName, $argExprs);
        $codecAccess = $builder->propertyFetch($builder->var('this'), $codecPropertyName);
        $coalesceAssign = new Coalesce($codecAccess, $newCodec);

        return new CompiledExpr($codecAccess, [new Expression($coalesceAssign)]);
    }

}
