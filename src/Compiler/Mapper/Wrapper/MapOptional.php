<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper\Wrapper;

use Attribute;
use PhpParser\Node\Expr;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\CompiledExpr;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\UndefinedAwareMapperCompiler;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Runtime\Optional;

#[Attribute(Attribute::TARGET_PARAMETER)]
class MapOptional implements UndefinedAwareMapperCompiler
{

    public function __construct(
        public readonly MapperCompiler $mapperCompiler,
    )
    {
    }

    public function compile(Expr $value, Expr $path, PhpCodeBuilder $builder): CompiledExpr
    {
        $mapper = $this->mapperCompiler->compile($value, $path, $builder);
        $mapped = $builder->staticCall($builder->importClass(Optional::class), 'of', [$mapper->expr]);
        return new CompiledExpr($mapped, $mapper->statements);
    }

    public function compileUndefined(Expr $path, PhpCodeBuilder $builder): CompiledExpr
    {
        $mapped = $builder->staticCall($builder->importClass(Optional::class), 'none');
        return new CompiledExpr($mapped);
    }

    /**
     * @return array<string, mixed>
     */
    public function getJsonSchema(): array
    {
        return $this->mapperCompiler->getJsonSchema();
    }

    public function getInputType(PhpCodeBuilder $builder): TypeNode
    {
        return $this->mapperCompiler->getInputType($builder);
    }

    public function getOutputType(PhpCodeBuilder $builder): TypeNode
    {
        return new GenericTypeNode(
            new IdentifierTypeNode($builder->importClass(Optional::class)),
            [$this->mapperCompiler->getOutputType($builder)],
        );
    }

}
