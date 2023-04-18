<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper\Object;

use Attribute;
use BackedEnum;
use PhpParser\Node\Expr;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\CompiledExpr;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Runtime\MappingFailedException;
use function array_column;
use function implode;

#[Attribute(Attribute::TARGET_PARAMETER)]
class MapEnum implements MapperCompiler
{

    /**
     * @param  class-string<BackedEnum> $enumName
     */
    public function __construct(
        public readonly string $enumName,
        public readonly MapperCompiler $backingValueMapperCompiler,
    )
    {
    }

    public function compile(Expr $value, Expr $path, PhpCodeBuilder $builder): CompiledExpr
    {
        $backingValueMapper = $this->backingValueMapperCompiler->compile($value, $path, $builder);
        $statements = $backingValueMapper->statements;

        $enumOrNull = $builder->staticCall($builder->importClass($this->enumName), 'tryFrom', [$backingValueMapper->expr]);
        $enumVariableName = $builder->uniqVariableName('enum');

        $statements[] = $builder->assign($builder->var($enumVariableName), $enumOrNull);
        $statements[] = $builder->if($builder->same($builder->var($enumVariableName), $builder->val(null)), [
            $builder->throw(
                $builder->staticCall(
                    $builder->importClass(MappingFailedException::class),
                    'incorrectValue',
                    [$value, $path, 'one of ' . implode(', ', array_column($this->enumName::cases(), 'value'))],
                ),
            ),
        ]);

        return new CompiledExpr($builder->var($enumVariableName), $statements);
    }

    /**
     * @return array<string, mixed>
     */
    public function getJsonSchema(): array
    {
        $schema = $this->backingValueMapperCompiler->getJsonSchema();
        $schema['enum'] = array_column($this->enumName::cases(), 'value');

        return $schema;
    }

    public function getInputType(PhpCodeBuilder $builder): TypeNode
    {
        return $this->backingValueMapperCompiler->getInputType($builder);
    }

    public function getOutputType(PhpCodeBuilder $builder): TypeNode
    {
        return new IdentifierTypeNode($builder->importClass($this->enumName));
    }

}