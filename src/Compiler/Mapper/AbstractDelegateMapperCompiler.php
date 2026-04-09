<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper;

use Nette\Utils\Arrays;
use PhpParser\Builder\Method;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\VariadicPlaceholder;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\CompiledExpr;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Runtime\CallbackMapper;
use function count;

abstract class AbstractDelegateMapperCompiler implements MapperCompiler
{

    /**
     * @param list<MapperCompiler> $innerMapperCompilers
     */
    public function __construct(
        public readonly string $className,
        public readonly array $innerMapperCompilers = [],
    )
    {
    }

    public function compile(
        Expr $value,
        Expr $path,
        PhpCodeBuilder $builder,
    ): CompiledExpr
    {
        $compilerMapper = $this->compileMapperExpr($builder);
        $mapper = $compilerMapper->expr;
        $statements = $compilerMapper->statements;
        $mapped = $builder->methodCall($mapper, 'map', [$value, $path]);

        return new CompiledExpr($mapped, $statements);
    }

    /**
     * Returns the type node for the class name with generic parameters applied.
     *
     * @param callable(MapperCompiler): TypeNode $innerTypeExtractor
     */
    protected function getClassType(callable $innerTypeExtractor): TypeNode
    {
        $classType = new IdentifierTypeNode($this->className);

        if (count($this->innerMapperCompilers) === 0) {
            return $classType;
        }

        return new GenericTypeNode($classType, Arrays::map(
            $this->innerMapperCompilers,
            static function (MapperCompiler $innerMapperCompiler) use ($innerTypeExtractor): TypeNode {
                return $innerTypeExtractor($innerMapperCompiler);
            },
        ));
    }

    /**
     * Returns the provider method name for fetching mappers at runtime.
     */
    abstract protected function getProviderMethodName(): string;

    /**
     * Creates a mapper method on the code builder.
     */
    abstract protected function buildMapperMethod(
        string $methodName,
        MapperCompiler $mapperCompiler,
        PhpCodeBuilder $builder,
    ): Method;

    /**
     * @return list<Expr>
     */
    private function compileInnerMappers(PhpCodeBuilder $builder): array
    {
        $innerMappers = [];

        foreach ($this->innerMapperCompilers as $key => $innerMapperCompiler) {
            $innerMappers[] = $this->compileInnerMapper($innerMapperCompiler, $key, $builder);
        }

        return $innerMappers;
    }

    private function compileInnerMapper(
        MapperCompiler $innerMapperCompiler,
        int $key,
        PhpCodeBuilder $builder,
    ): Expr
    {
        if ($innerMapperCompiler instanceof static && count($innerMapperCompiler->innerMapperCompilers) === 0) {
            $provider = $builder->propertyFetch($builder->var('this'), 'provider');
            $innerClassExpr = $builder->classConstFetch($builder->importClass($innerMapperCompiler->className), 'class');
            return $builder->methodCall($provider, $this->getProviderMethodName(), [$innerClassExpr]);
        }

        $innerMapperMethodName = $builder->uniqMethodName("mapInner{$key}");
        $innerMapperMethod = $this->buildMapperMethod($innerMapperMethodName, $innerMapperCompiler, $builder)->makePrivate()->getNode();
        $builder->addMethod($innerMapperMethod);

        $innerMapperMethodCallback = new MethodCall($builder->var('this'), $innerMapperMethodName, [new VariadicPlaceholder()]);
        return $builder->new($builder->importClass(CallbackMapper::class), [$innerMapperMethodCallback]);
    }

    private function compileMapperExpr(PhpCodeBuilder $builder): CompiledExpr
    {
        foreach ($builder->getGenericParameters() as $offset => $genericParameter) {
            if ($this->className === $genericParameter->name) {
                $innerMappers = $builder->propertyFetch($builder->var('this'), 'genericInnerMappers');
                $innerMapper = $builder->arrayDimFetch($innerMappers, $builder->val($offset));
                return new CompiledExpr($innerMapper);
            }
        }

        $statements = [];
        $classNameExpr = $builder->classConstFetch($builder->importClass($this->className), 'class');
        $provider = $builder->propertyFetch($builder->var('this'), 'provider');
        $innerMappers = $this->compileInnerMappers($builder);

        if (count($innerMappers) > 0) {
            $innerMappersVarName = $builder->uniqVariableName('genericInnerMappers');
            $statements[] = $builder->assign($builder->var($innerMappersVarName), $builder->val($innerMappers));
            $getArguments = [$classNameExpr, $builder->var($innerMappersVarName)];

        } else {
            $getArguments = [$classNameExpr];
        }

        $mapper = $builder->methodCall($provider, $this->getProviderMethodName(), $getArguments);
        return new CompiledExpr($mapper, $statements);
    }

}
