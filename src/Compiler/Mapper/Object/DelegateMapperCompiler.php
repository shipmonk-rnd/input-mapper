<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper\Object;

use Nette\Utils\Arrays;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\VariadicPlaceholder;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\CompiledExpr;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Runtime\CallbackMapper;
use function count;

class DelegateMapperCompiler implements MapperCompiler
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

    public function compile(Expr $value, Expr $path, PhpCodeBuilder $builder): CompiledExpr
    {
        $compilerMapper = $this->compileMapperExpr($builder);
        $mapper = $compilerMapper->expr;
        $statements = $compilerMapper->statements;
        $mapped = $builder->methodCall($mapper, 'map', [$value, $path]);

        return new CompiledExpr($mapped, $statements);
    }

    public function getInputType(): TypeNode
    {
        return new IdentifierTypeNode('mixed');
    }

    public function getOutputType(): TypeNode
    {
        $outputType = new IdentifierTypeNode($this->className);

        if (count($this->innerMapperCompilers) === 0) {
            return $outputType;
        }

        return new GenericTypeNode($outputType, Arrays::map(
            $this->innerMapperCompilers,
            static function (MapperCompiler $innerMapperCompiler): TypeNode {
                return $innerMapperCompiler->getOutputType();
            },
        ));
    }

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

    private function compileInnerMapper(MapperCompiler $innerMapperCompiler, int $key, PhpCodeBuilder $builder): Expr
    {
        if ($innerMapperCompiler instanceof self && count($innerMapperCompiler->innerMapperCompilers) === 0) {
            $provider = $builder->propertyFetch($builder->var('this'), 'provider');
            $innerClassExpr = $builder->classConstFetch($builder->importClass($innerMapperCompiler->className), 'class');
            return $builder->methodCall($provider, 'get', [$innerClassExpr]);
        }

        $innerMapperMethodName = $builder->uniqMethodName("mapInner{$key}");
        $innerMapperMethod = $builder->mapperMethod($innerMapperMethodName, $innerMapperCompiler)->makePrivate()->getNode();
        $builder->addMethod($innerMapperMethod);

        $innerMapperMethodCallback = new MethodCall($builder->var('this'), $innerMapperMethodName, [new VariadicPlaceholder()]);
        return $builder->new($builder->importClass(CallbackMapper::class), [$innerMapperMethodCallback]);
    }

    private function compileMapperExpr(PhpCodeBuilder $builder): CompiledExpr
    {
        foreach ($builder->getGenericParameters() as $offset => $genericParameter) {
            if ($this->className === $genericParameter->name) {
                $innerMappers = $builder->propertyFetch($builder->var('this'), 'innerMappers');
                $innerMapper = $builder->arrayDimFetch($innerMappers, $builder->val($offset));
                return new CompiledExpr($innerMapper);
            }
        }

        $statements = [];
        $classNameExpr = $builder->classConstFetch($builder->importClass($this->className), 'class');
        $provider = $builder->propertyFetch($builder->var('this'), 'provider');
        $innerMappers = $this->compileInnerMappers($builder);

        if (count($innerMappers) > 0) {
            $innerMappersVarName = $builder->uniqVariableName('innerMappers');
            $statements[] = $builder->assign($builder->var($innerMappersVarName), $builder->val($innerMappers));
            $getArguments = [$classNameExpr, $builder->var($innerMappersVarName)];

        } else {
            $getArguments = [$classNameExpr];
        }

        $mapper = $builder->methodCall($provider, 'get', $getArguments);
        return new CompiledExpr($mapper, $statements);
    }

}
