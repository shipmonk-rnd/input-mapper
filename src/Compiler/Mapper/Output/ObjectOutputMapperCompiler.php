<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper\Output;

use Nette\Utils\Arrays;
use PhpParser\Node\Expr;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeItemNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\CompiledExpr;
use ShipMonk\InputMapper\Compiler\Mapper\GenericMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Compiler\Type\GenericTypeParameter;
use function count;
use function ucfirst;

/**
 * @template T of object
 */
class ObjectOutputMapperCompiler implements GenericMapperCompiler
{

    /**
     * @param class-string<T> $className
     * @param array<string, array{string, MapperCompiler}> $propertyMapperCompilers propertyName => [outputKey, MapperCompiler]
     * @param list<GenericTypeParameter> $genericParameters
     */
    public function __construct(
        public readonly string $className,
        public readonly array $propertyMapperCompilers,
        public readonly array $genericParameters = [],
    )
    {
    }

    public function compile(
        Expr $value,
        Expr $path,
        PhpCodeBuilder $builder,
    ): CompiledExpr
    {
        $hasOptionalProperties = false;

        foreach ($this->propertyMapperCompilers as [$outputKey, $propertyMapperCompiler]) {
            if ($propertyMapperCompiler instanceof OptionalOutputMapperCompiler) {
                $hasOptionalProperties = true;
                break;
            }
        }

        if ($hasOptionalProperties) {
            return $this->compileWithOptionalProperties($value, $path, $builder);
        }

        $arrayItems = [];

        foreach ($this->propertyMapperCompilers as $propertyName => [$outputKey, $propertyMapperCompiler]) {
            $propertyAccess = $builder->propertyFetch($value, $propertyName);
            $mappedValue = $this->compilePropertyValue($propertyAccess, $path, $propertyName, $propertyMapperCompiler, $builder);
            $arrayItems[] = $builder->arrayItem($mappedValue, $builder->val($outputKey));
        }

        return new CompiledExpr($builder->array($arrayItems));
    }

    private function compileWithOptionalProperties(
        Expr $value,
        Expr $path,
        PhpCodeBuilder $builder,
    ): CompiledExpr
    {
        $outputVariableName = $builder->uniqVariableName('output');
        $statements = [
            $builder->assign($builder->var($outputVariableName), $builder->val([])),
        ];

        foreach ($this->propertyMapperCompilers as $propertyName => [$outputKey, $propertyMapperCompiler]) {
            $propertyAccess = $builder->propertyFetch($value, $propertyName);
            $mappedValue = $this->compilePropertyValue($propertyAccess, $path, $propertyName, $propertyMapperCompiler, $builder);

            $assignment = $builder->assign(
                $builder->arrayDimFetch($builder->var($outputVariableName), $builder->val($outputKey)),
                $mappedValue,
            );

            if ($propertyMapperCompiler instanceof OptionalOutputMapperCompiler) {
                $statements[] = $builder->if(
                    $builder->methodCall($propertyAccess, 'isDefined'),
                    [$assignment],
                );
            } else {
                $statements[] = $assignment;
            }
        }

        return new CompiledExpr($builder->var($outputVariableName), $statements);
    }

    private function compilePropertyValue(
        Expr $propertyAccess,
        Expr $path,
        string $propertyName,
        MapperCompiler $propertyMapperCompiler,
        PhpCodeBuilder $builder,
    ): Expr
    {
        $propertyPath = $builder->arrayImmutableAppend($path, $builder->val($propertyName));
        $compiled = $propertyMapperCompiler->compile($propertyAccess, $propertyPath, $builder);

        if ($compiled->statements === []) {
            return $compiled->expr;
        }

        $propertyMapperMethodName = $builder->uniqMethodName('map' . ucfirst($propertyName));
        $propertyMapperMethod = $builder->mapperMethod($propertyMapperMethodName, $propertyMapperCompiler)->makePrivate()->getNode();
        $builder->addMethod($propertyMapperMethod);

        return $builder->methodCall($builder->var('this'), $propertyMapperMethodName, [$propertyAccess, $propertyPath]);
    }

    public function getInputType(): TypeNode
    {
        $inputType = new IdentifierTypeNode($this->className);

        if (count($this->genericParameters) === 0) {
            return $inputType;
        }

        return new GenericTypeNode(
            $inputType,
            Arrays::map($this->genericParameters, static function (GenericTypeParameter $parameter): TypeNode {
                return new IdentifierTypeNode($parameter->name);
            }),
        );
    }

    public function getOutputType(): TypeNode
    {
        $items = [];

        foreach ($this->propertyMapperCompilers as [$outputKey, $propertyMapperCompiler]) {
            $optional = $propertyMapperCompiler instanceof OptionalOutputMapperCompiler;
            $items[] = new ArrayShapeItemNode(
                new IdentifierTypeNode($outputKey),
                $optional,
                $propertyMapperCompiler->getOutputType(),
            );
        }

        return ArrayShapeNode::createSealed($items);
    }

    /**
     * @return list<GenericTypeParameter>
     */
    public function getGenericParameters(): array
    {
        return $this->genericParameters;
    }

}
