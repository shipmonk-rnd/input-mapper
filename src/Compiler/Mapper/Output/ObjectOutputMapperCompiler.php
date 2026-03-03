<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper\Output;

use Nette\Utils\Arrays;
use PhpParser\Node\Expr;
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
        $statements = [];
        $arrayItems = [];

        foreach ($this->propertyMapperCompilers as $propertyName => [$outputKey, $propertyMapperCompiler]) {
            $propertyAccess = $builder->propertyFetch($value, $propertyName);
            $propertyPath = $builder->arrayImmutableAppend($path, $builder->val($propertyName));
            $propertyMapperMethodName = $builder->uniqMethodName('map' . ucfirst($propertyName));
            $propertyMapperMethod = $builder->outputMapperMethod($propertyMapperMethodName, $propertyMapperCompiler)->makePrivate()->getNode();
            $propertyMapperCall = $builder->methodCall($builder->var('this'), $propertyMapperMethodName, [$propertyAccess, $propertyPath]);
            $builder->addMethod($propertyMapperMethod);

            $arrayItems[] = $builder->arrayItem($propertyMapperCall, $builder->val($outputKey));
        }

        return new CompiledExpr(
            $builder->array($arrayItems),
            $statements,
        );
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
        return new IdentifierTypeNode('mixed');
    }

    /**
     * @return list<GenericTypeParameter>
     */
    public function getGenericParameters(): array
    {
        return $this->genericParameters;
    }

}
