<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper\Object;

use Attribute;
use DateTimeImmutable;
use DateTimeInterface;
use PhpParser\Node\Expr;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\CompiledExpr;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;

#[Attribute(Attribute::TARGET_PARAMETER)]
class MapDateTimeImmutable implements MapperCompiler
{

    public function __construct(
        public readonly string $format = DateTimeInterface::RFC3339,
        public readonly string $formatDescription = 'date-time string in RFC 3339 format',
    )
    {
    }

    public function compile(Expr $value, Expr $path, PhpCodeBuilder $builder): CompiledExpr
    {
        $mappedVariableName = $builder->uniqVariableName('mapped');

        $statements = [
            $builder->if($builder->not($builder->funcCall($builder->importFunction('is_string'), [$value])), [
                $builder->throw(
                    $builder->staticCall(
                        $builder->importClass(MappingFailedException::class),
                        'incorrectType',
                        [$value, $path, $builder->val('string')],
                    ),
                ),
            ]),

            $builder->assign(
                $builder->var($mappedVariableName),
                $builder->staticCall($builder->importClass(DateTimeImmutable::class), 'createFromFormat', [
                    $this->format,
                    $value,
                ]),
            ),

            $builder->if($builder->same($builder->var($mappedVariableName), $builder->val(false)), [
                $builder->throw(
                    $builder->staticCall(
                        $builder->importClass(MappingFailedException::class),
                        'incorrectValue',
                        [$value, $path, $this->formatDescription],
                    ),
                ),
            ]),
        ];

        return new CompiledExpr($builder->var($mappedVariableName), $statements);
    }

    public function getInputType(PhpCodeBuilder $builder): TypeNode
    {
        return new IdentifierTypeNode('mixed');
    }

    public function getOutputType(PhpCodeBuilder $builder): TypeNode
    {
        return new IdentifierTypeNode($builder->importClass(DateTimeImmutable::class));
    }

}
