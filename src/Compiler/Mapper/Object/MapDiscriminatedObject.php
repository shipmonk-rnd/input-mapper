<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper\Object;

use Attribute;
use Nette\Utils\Arrays;
use PhpParser\Node\Expr;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\CompiledExpr;
use ShipMonk\InputMapper\Compiler\Exception\CannotCompileMapperException;
use ShipMonk\InputMapper\Compiler\Mapper\GenericMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Compiler\Type\GenericTypeParameter;
use ShipMonk\InputMapper\Compiler\Type\PhpDocTypeUtils;
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
     * @param array<string, MapperCompiler> $subtypeCompilers
     * @param list<GenericTypeParameter> $genericParameters
     */
    public function __construct(
        public readonly string $className,
        public readonly string $discriminatorKeyName,
        public readonly array $subtypeCompilers,
        public readonly array $genericParameters = [],
    )
    {
    }

    public function compile(Expr $value, Expr $path, PhpCodeBuilder $builder): CompiledExpr
    {
        foreach ($this->subtypeCompilers as $subtypeCompiler) {
            if (!PhpDocTypeUtils::isSubTypeOf($subtypeCompiler->getOutputType(), $this->getOutputType())) {
                throw CannotCompileMapperException::withIncompatibleSubtypeMapper($this, $subtypeCompiler);
            }
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

        $discriminatorKeyAsValue = $builder->val($this->discriminatorKeyName);

        $isDiscriminatorPresent = $builder->funcCall($builder->importFunction('array_key_exists'), [$discriminatorKeyAsValue, $value]);
        $isDiscriminatorMissing = $builder->not($isDiscriminatorPresent);

        $statements[] = $builder->if($isDiscriminatorMissing, [
            $builder->throw(
                $builder->staticCall(
                    $builder->importClass(MappingFailedException::class),
                    'missingKey',
                    [$path, $discriminatorKeyAsValue],
                ),
            ),
        ]);

        $discriminatorRawValue = $builder->arrayDimFetch($value, $discriminatorKeyAsValue);
        $discriminatorPath = $builder->arrayImmutableAppend($path, $discriminatorKeyAsValue);

        $validMappingKeys = array_keys($this->subtypeCompilers);

        $expectedDescription = $builder->concat(
            'one of ',
            $builder->funcCall($builder->importFunction('implode'), [
                ', ',
                $builder->val($validMappingKeys),
            ]),
        );

        $subtypeMatchArms = [];

        foreach ($this->subtypeCompilers as $key => $subtypeCompiler) {
            $subtypeMapperMethodName = $builder->uniqMethodName('map' . ucfirst($key));
            $subtypeMapperMethod = $builder->mapperMethod($subtypeMapperMethodName, $subtypeCompiler)->makePrivate()->getNode();

            $builder->addMethod($subtypeMapperMethod);
            $subtypeMapperMethodCall = $builder->methodCall($builder->var('this'), $subtypeMapperMethodName, [$value, $path]);

            $subtypeMatchArms[] = $builder->matchArm(
                $builder->val($key),
                $subtypeMapperMethodCall,
            );
        }

        $subtypeMatchArms[] = $builder->matchArm(
            null,
            $builder->throwExpr(
                $builder->staticCall(
                    $builder->importClass(MappingFailedException::class),
                    'incorrectValue',
                    [$discriminatorRawValue, $discriminatorPath, $expectedDescription],
                ),
            ),
        );

        $matchedSubtype = $builder->match($discriminatorRawValue, $subtypeMatchArms);

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
