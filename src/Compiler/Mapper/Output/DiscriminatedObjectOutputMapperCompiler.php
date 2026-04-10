<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper\Output;

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
use function count;
use function ucfirst;

/**
 * @template T of object
 */
class DiscriminatedObjectOutputMapperCompiler implements GenericMapperCompiler
{

    /**
     * @param class-string<T> $className
     * @param array<string, MapperCompiler> $subtypeCompilers discriminatorValue => MapperCompiler
     * @param list<GenericTypeParameter> $genericParameters
     */
    public function __construct(
        public readonly string $className,
        public readonly array $subtypeCompilers,
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
        foreach ($this->subtypeCompilers as $subtypeCompiler) {
            if (!PhpDocTypeUtils::isSubTypeOf($subtypeCompiler->getInputType(), $this->getInputType())) {
                throw CannotCompileMapperException::withIncompatibleSubtypeMapper($this, $subtypeCompiler, 'input');
            }
        }

        $subtypeMatchArms = [];

        foreach ($this->subtypeCompilers as $key => $subtypeCompiler) {
            $subtypeMapperMethodName = $builder->uniqMethodName('map' . ucfirst($key));
            $subtypeMapperMethod = $builder->mapperMethod($subtypeMapperMethodName, $subtypeCompiler)->makePrivate()->getNode();

            $builder->addMethod($subtypeMapperMethod);
            $subtypeMapperMethodCall = $builder->methodCall($builder->var('this'), $subtypeMapperMethodName, [$value, $path]);

            $subtypeMatchArms[] = $builder->matchArm(
                $builder->instanceOf($value, $builder->importClass($subtypeCompiler->getInputType()->__toString())),
                $subtypeMapperMethodCall,
            );
        }

        $subtypeMatchArms[] = $builder->matchArm(
            null,
            $builder->throwExpr(
                $builder->staticCall(
                    $builder->importClass(MappingFailedException::class),
                    'incorrectType',
                    [$value, $path, $builder->val($this->className)],
                ),
            ),
        );

        $matchedSubtype = $builder->match($builder->val(true), $subtypeMatchArms);

        return new CompiledExpr($matchedSubtype);
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
        return new IdentifierTypeNode('mixed'); // exact type unknown because subtypes are resolved at runtime via delegates
    }

    /**
     * @return list<GenericTypeParameter>
     */
    public function getGenericParameters(): array
    {
        return $this->genericParameters;
    }

}
