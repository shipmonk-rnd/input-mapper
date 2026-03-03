<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper\Output;

use BackedEnum;
use PhpParser\Node\Expr;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ReflectionEnum;
use ShipMonk\InputMapper\Compiler\CompiledExpr;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;

class EnumOutputMapperCompiler implements MapperCompiler
{

    /**
     * @param class-string<BackedEnum> $enumName
     */
    public function __construct(
        public readonly string $enumName,
    )
    {
    }

    public function compile(
        Expr $value,
        Expr $path,
        PhpCodeBuilder $builder,
    ): CompiledExpr
    {
        return new CompiledExpr($builder->propertyFetch($value, 'value'));
    }

    public function getInputType(): TypeNode
    {
        return new IdentifierTypeNode($this->enumName);
    }

    public function getOutputType(): TypeNode
    {
        $enumReflection = new ReflectionEnum($this->enumName);
        $backingType = $enumReflection->getBackingType();

        if ($backingType !== null) {
            $typeName = $backingType->getName();
            return new IdentifierTypeNode($typeName);
        }

        return new IdentifierTypeNode('mixed');
    }

}
