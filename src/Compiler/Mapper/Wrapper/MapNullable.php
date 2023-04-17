<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper\Wrapper;

use Attribute;
use LogicException;
use PhpParser\Node\Expr;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\CompiledExpr;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Compiler\Type\PhpDocTypeUtils;
use function is_array;
use function is_string;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class MapNullable implements MapperCompiler
{

    public function __construct(
        public readonly MapperCompiler $mapperCompiler,
    )
    {
    }

    public function compile(Expr $value, Expr $path, PhpCodeBuilder $builder): CompiledExpr
    {
        $mapper = $this->mapperCompiler->compile($value, $path, $builder);
        $mappedVariableName = $builder->uniqVariableName('mapped');

        $statements = [
            $builder->if(
                $builder->same($value, $builder->val(null)),
                [
                    $builder->assign($builder->var($mappedVariableName), $builder->val(null)),
                ],
                [
                    ...$mapper->statements,
                    $builder->assign($builder->var($mappedVariableName), $mapper->expr),
                ],
            ),
        ];

        return new CompiledExpr($builder->var($mappedVariableName), $statements);
    }

    /**
     * @return array<string, mixed>
     */
    public function getJsonSchema(): array
    {
        $schema = $this->mapperCompiler->getJsonSchema();

        if (!isset($schema['type'])) {
            $schema['type'] = ['null'];
        } elseif (is_string($schema['type'])) {
            $schema['type'] = [$schema['type'], 'null'];
        } elseif (is_array($schema['type'])) {
            $schema['type'][] = 'null';
        } else {
            throw new LogicException('Unexpected type of type');
        }

        return $schema;
    }

    public function getInputType(PhpCodeBuilder $builder): TypeNode
    {
        return PhpDocTypeUtils::makeNullable($this->mapperCompiler->getInputType($builder));
    }

    public function getOutputType(PhpCodeBuilder $builder): TypeNode
    {
        return PhpDocTypeUtils::makeNullable($this->mapperCompiler->getOutputType($builder));
    }

}
