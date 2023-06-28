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
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
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

        $expectedDescription = $builder->concat(
            'one of ',
            $builder->funcCall($builder->importFunction('implode'), [
                ', ',
                $builder->funcCall($builder->importFunction('array_column'), [
                    $builder->staticCall($builder->importClass($this->enumName), 'cases'),
                    $builder->val('value'),
                ]),
            ]),
        );

        $statements[] = $builder->assign($builder->var($enumVariableName), $enumOrNull);
        $statements[] = $builder->if($builder->same($builder->var($enumVariableName), $builder->val(null)), [
            $builder->throw(
                $builder->staticCall(
                    $builder->importClass(MappingFailedException::class),
                    'incorrectValue',
                    [$value, $path, $expectedDescription],
                ),
            ),
        ]);

        return new CompiledExpr($builder->var($enumVariableName), $statements);
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
