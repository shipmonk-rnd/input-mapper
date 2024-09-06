<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper\Object;

use Attribute;
use Nette\Utils\Arrays;
use PhpParser\Node\Expr;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\CompiledExpr;
use ShipMonk\InputMapper\Compiler\Mapper\GenericMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapString;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Compiler\Type\GenericTypeParameter;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use function array_keys;
use function count;
use function ucfirst;

/**
 * @template T of object
 */
#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class MapDiscriminatedObject implements GenericMapperCompiler
{

    /**
     * @param class-string<T> $className
     * @param array<string, MapperCompiler> $objectMappers
     * @param list<GenericTypeParameter> $genericParameters
     */
    public function __construct(
        public readonly string $className,
        public readonly string $discriminatorFieldName,
        public readonly array $objectMappers,
        public readonly array $genericParameters = [],
    )
    {
    }

    public function compile(Expr $value, Expr $path, PhpCodeBuilder $builder): CompiledExpr
    {
        $objectMapperMethodCalls = [];

        foreach ($this->objectMappers as $key => $objectMapper) {
            $objectMapperMethodName = $builder->uniqMethodName('map' . ucfirst($key));
            $objectMapperMethod = $builder->mapperMethod($objectMapperMethodName, $objectMapper)->makePrivate()->getNode();
            $objectMapperMethodCall = $builder->methodCall($builder->var('this'), $objectMapperMethodName, [$value, $path]);
            $objectMapperMethodCalls[$key] = $objectMapperMethodCall;

            $builder->addMethod($objectMapperMethod);
        }

        $statements = [
            $builder->if($builder->not($builder->funcCall($builder->importFunction('is_array'), [$value])), [
                $builder->throw(
                    $builder->staticCall(
                        $builder->importClass(MappingFailedException::class),
                        'incorrectType',
                        [$value, $path, $builder->val('array')],
                    ),
                ),
            ]),
        ];

        $isDiscriminatorPresent = $builder->funcCall($builder->importFunction('array_key_exists'), [$builder->val($this->discriminatorFieldName), $value]);
        $isDiscriminatorMissing = $builder->not($isDiscriminatorPresent);

        $statements[] = $builder->if($isDiscriminatorMissing, [
            $builder->throw(
                $builder->staticCall(
                    $builder->importClass(MappingFailedException::class),
                    'missingKey',
                    [$path, $this->discriminatorFieldName],
                ),
            ),
        ]);

        $discriminatorRawValue = $builder->arrayDimFetch($value, $builder->val($this->discriminatorFieldName));
        $discriminatorPath = $builder->arrayImmutableAppend($path, $builder->val($this->discriminatorFieldName));
        $discriminatorMapperMethodName = $builder->uniqMethodName('map' . ucfirst($this->discriminatorFieldName));
        $discriminatorMapperMethod = $builder->mapperMethod($discriminatorMapperMethodName, new MapString())->makePrivate()->getNode();
        $discriminatorMapperCall = $builder->methodCall($builder->var('this'), $discriminatorMapperMethodName, [$discriminatorRawValue, $discriminatorPath]);
        $builder->addMethod($discriminatorMapperMethod);

        $validMappingKeys = array_keys($this->objectMappers);
        $isDiscriminatorValid = $builder->funcCall($builder->importFunction('in_array'), [$discriminatorRawValue, $builder->val($validMappingKeys), $builder->val(true)]);

        $expectedDescription = $builder->concat(
            'one of ',
            $builder->funcCall($builder->importFunction('implode'), [
                ', ',
                $builder->val($validMappingKeys),
            ]),
        );

        $statements[] = $builder->if($builder->not($isDiscriminatorValid), [
            $builder->throw(
                $builder->staticCall(
                    $builder->importClass(MappingFailedException::class),
                    'incorrectValue',
                    [$discriminatorRawValue, $discriminatorPath, $expectedDescription],
                ),
            ),
        ]);

        $subtypeMatchArms = [];

        foreach ($objectMapperMethodCalls as $key => $objectMapperMethodCall) {
            $subtypeMatchArms[] = $builder->matchArm(
                $builder->val($key),
                $objectMapperMethodCall,
            );
        }

        $matchedSubtype = $builder->match($discriminatorMapperCall, $subtypeMatchArms);

        return new CompiledExpr(
            $matchedSubtype,
            $statements,
        );
    }

    public function getInputType(): TypeNode
    {
        return new IdentifierTypeNode('mixed');
    }

    public function getOutputType(): TypeNode
    {
        $outputType = new IdentifierTypeNode($this->className);

        if (count($this->genericParameters) === 0) {
            return $outputType;
        }

        return new GenericTypeNode(
            $outputType,
            Arrays::map($this->genericParameters, static function (GenericTypeParameter $parameter): TypeNode {
                return new IdentifierTypeNode($parameter->name);
            }),
        );
    }

    /**
     * @return list<GenericTypeParameter>
     */
    public function getGenericParameters(): array
    {
        return $this->genericParameters;
    }

}
