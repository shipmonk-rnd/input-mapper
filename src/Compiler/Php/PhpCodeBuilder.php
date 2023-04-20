<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Php;

use LogicException;
use PhpParser\Builder\Class_;
use PhpParser\Builder\Method;
use PhpParser\BuilderFactory;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use PhpParser\Node\Expr\BinaryOp\BooleanOr;
use PhpParser\Node\Expr\BinaryOp\Greater;
use PhpParser\Node\Expr\BinaryOp\GreaterOrEqual;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use PhpParser\Node\Expr\BinaryOp\Smaller;
use PhpParser\Node\Expr\BinaryOp\SmallerOrEqual;
use PhpParser\Node\Expr\BooleanNot;
use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Class_ as ClassNode;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Else_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Stmt\Throw_;
use PhpParser\Node\Stmt\Use_;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use ReflectionClass;
use ShipMonk\InputMapper\Compiler\CompiledExpr;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\PhpDoc\PhpDocHelper;
use ShipMonk\InputMapper\Compiler\Type\PhpDocTypeUtils;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use ShipMonk\InputMapper\Runtime\MappingFailedException;
use function array_pop;
use function array_reverse;
use function array_values;
use function assert;
use function count;
use function ksort;
use function str_ends_with;

class PhpCodeBuilder extends BuilderFactory
{

    /**
     * @var array<string, ClassMethod>
     */
    private array $methods = [];

    /**
     * @var array<string, string>
     */
    private array $classLikeImports = [];

    /**
     * @var array<string, string>
     */
    private array $functionImports = [];

    /**
     * @var array<int, array<string, bool>>
     */
    private array $variables = [];

    public function ternary(Expr $cond, Expr $ifTrue, Expr $else): Ternary
    {
        return new Ternary($cond, $ifTrue, $else);
    }

    public function and(Expr $operand, Expr ...$rest): Expr
    {
        return count($rest) === 0
            ? $operand
            : new BooleanAnd($operand, $this->and(...$rest));
    }

    public function or(Expr $operand, Expr ...$rest): Expr
    {
        return count($rest) === 0
            ? $operand
            : new BooleanOr($operand, $this->or(...$rest));
    }

    /**
     * @param list<Stmt>      $ifTrue
     * @param list<Stmt>|null $else
     */
    public function if(Expr $cond, array $ifTrue, ?array $else = null): If_
    {
        return new If_($cond, ['stmts' => $ifTrue, 'else' => $else !== null ? new Else_($else) : null]);
    }

    public function not(Expr $expr): BooleanNot
    {
        return new BooleanNot($expr);
    }

    public function same(Expr $left, Expr $right): Identical
    {
        return new Identical($left, $right);
    }

    public function notSame(Expr $left, Expr $right): NotIdentical
    {
        return new NotIdentical($left, $right);
    }

    public function lt(Expr $left, Expr $right): Smaller
    {
        return new Smaller($left, $right);
    }

    public function lte(Expr $left, Expr $right): SmallerOrEqual
    {
        return new SmallerOrEqual($left, $right);
    }

    public function gt(Expr $left, Expr $right): Greater
    {
        return new Greater($left, $right);
    }

    public function gte(Expr $left, Expr $right): GreaterOrEqual
    {
        return new GreaterOrEqual($left, $right);
    }

    /**
     * @param array<int|string, scalar|array<mixed>|Expr|Arg|null> $args
     */
    public function throwNew(string $className, array $args): Throw_
    {
        return $this->throw($this->new($className, $args));
    }

    public function throw(Expr $expr): Throw_
    {
        return new Throw_($expr);
    }

    public function arrayDimFetch(Expr $var, ?Expr $dim = null): ArrayDimFetch
    {
        return new ArrayDimFetch($var, $dim);
    }

    public function assign(Expr $var, Expr $expr): Expression
    {
        return new Expression(new Assign($var, $expr));
    }

    public function return(Expr $expr): Return_
    {
        return new Return_($expr);
    }

    /**
     * @param array<?ArrayItem> $items
     */
    public function array(array $items): Array_
    {
        return new Array_($items, ['kind' => Array_::KIND_SHORT]);
    }

    public function arrayItem(Expr $value, ?Expr $key): ArrayItem
    {
        return new ArrayItem($value, $key);
    }

    public function arrayImmutableAppend(Expr $path, Expr $item): Expr
    {
        if ($path instanceof Array_) {
            return $this->array([...$path->items, new ArrayItem($this->val($item))]);
        }

        return $this->array([new ArrayItem($path, unpack: true), new ArrayItem($this->val($item))]);
    }

    /**
     * @param list<Stmt> $statements
     */
    public function foreach(Expr $expr, Expr $value, Expr $key, array $statements): Foreach_
    {
        return new Foreach_($expr, $value, ['stmts' => $statements, 'keyVar' => $key]);
    }

    public function uniqMethodName(string $name): string
    {
        $i = 1;
        $uniqueName = $name;

        while (isset($this->methods[$uniqueName])) {
            $uniqueName = $name . ++$i;
        }

        return $uniqueName;
    }

    public function uniqVariableName(string $name): string
    {
        $i = 1;
        $uniqueName = $name;

        $scopeIndex = count($this->variables) - 1;

        if (!isset($this->variables[$scopeIndex])) {
            throw new LogicException('Unable to create unique variable name outside of variable scope');
        }

        while (isset($this->variables[$scopeIndex][$uniqueName])) {
            $uniqueName = $name . ++$i;
        }

        $this->variables[$scopeIndex][$uniqueName] = true;
        return $uniqueName;
    }

    /**
     * @return list<string>
     */
    public function uniqVariableNames(string ...$names): array
    {
        $uniqueNames = [];

        foreach ($names as $name) {
            $uniqueNames[] = $this->uniqVariableName($name);
        }

        return $uniqueNames;
    }

    /**
     * @template T
     * @param  callable(): T $cb
     * @return T
     */
    public function withVariableScope(callable $cb): mixed
    {
        try {
            $this->variables[] = [];
            return $cb();
        } finally {
            array_pop($this->variables);
        }
    }

    public function addMethod(ClassMethod $method): void
    {
        if (isset($this->methods[$method->name->name])) {
            throw new LogicException('Method already exists');
        }

        $this->methods[$method->name->name] = $method;
    }

    /**
     * @param class-string $className
     */
    public function importClass(string $className): string
    {
        $classReflection = new ReflectionClass($className);
        $shortName = $classReflection->getShortName();

        $i = 0;
        $uniqueName = $shortName;

        while (isset($this->classLikeImports[$uniqueName])) {
            if ($this->classLikeImports[$uniqueName] === $className) {
                return $uniqueName;
            }

            $uniqueName = $shortName . ++$i;
        }

        $this->classLikeImports[$uniqueName] = $className;
        return $uniqueName;
    }

    public function importFunction(string $functionName): string
    {
        $i = 0;
        $uniqueName = $functionName;

        while (isset($this->functionImports[$uniqueName])) {
            if ($this->functionImports[$uniqueName] === $functionName) {
                return $uniqueName;
            }

            $uniqueName = $functionName . ++$i;
        }

        $this->functionImports[$uniqueName] = $functionName;
        return $uniqueName;
    }

    public function mapperMethod(string $methodName, MapperCompiler $mapperCompiler): Method
    {
        /** @var CompiledExpr $mapper */
        $mapper = $this->withVariableScope(function () use ($mapperCompiler, &$dataVarName, &$pathVarName): CompiledExpr {
            [$dataVarName, $pathVarName] = $this->uniqVariableNames('data', 'path');
            return $mapperCompiler->compile($this->var($dataVarName), $this->var($pathVarName), $this);
        });

        assert($dataVarName !== null && $pathVarName !== null);
        $inputType = $mapperCompiler->getInputType($this);
        $outputType = $mapperCompiler->getOutputType($this);

        $nativeInputType = PhpDocTypeUtils::toNativeType($inputType, $phpDocInputTypeUseful);
        $nativeOutputType = PhpDocTypeUtils::toNativeType($outputType, $phpDocOutputTypeUseful);

        $phpDoc = PhpDocHelper::fromLines([
            $phpDocInputTypeUseful ? "@param  {$inputType} \${$dataVarName}" : null,
            "@param  list<string|int> \${$pathVarName}",
            $phpDocOutputTypeUseful ? "@return {$outputType}" : null,
            '@throws ' . $this->importClass(MappingFailedException::class),
        ]);

        return $this->method($methodName)
            ->setDocComment($phpDoc)
            ->addParam($this->param($dataVarName)->setType($nativeInputType))
            ->addParam($this->param($pathVarName)->setType('array')->setDefault($this->array([])))
            ->setReturnType($nativeOutputType)
            ->addStmts($mapper->statements)
            ->addStmt($this->return($mapper->expr));
    }

    public function mapperClass(string $shortClassName, MapperCompiler $mapperCompiler): Class_
    {
        $providerType = $this->importClass(MapperProvider::class);
        $providerParameter = $this->param('provider')->setType($providerType)->getNode();
        $providerParameter->flags = ClassNode::MODIFIER_PRIVATE | ClassNode::MODIFIER_READONLY;

        $mapperConstructor = $this->method('__construct')
            ->makePublic()
            ->addParam($providerParameter)
            ->getNode();

        $mapMethod = $this->mapperMethod('map', $mapperCompiler)
            ->makePublic()
            ->getNode();

        $outputType = $mapperCompiler->getOutputType($this);
        $implementsType = new GenericTypeNode(
            new IdentifierTypeNode($this->importClass(Mapper::class)),
            [$outputType],
        );

        $phpDoc = PhpDocHelper::fromLines([
            'Generated mapper. Do not edit directly.',
            '',
            "@implements {$implementsType}",
        ]);

        return $this->class($shortClassName)
            ->setDocComment($phpDoc)
            ->implement($this->importClass(Mapper::class))
            ->addStmt($mapperConstructor)
            ->addStmt($mapMethod)
            ->addStmts(array_reverse($this->getMethods()));
    }

    /**
     * @return list<ClassMethod>
     */
    public function getMethods(): array
    {
        return array_values($this->methods);
    }

    /**
     * @return list<Use_>
     */
    public function getImports(): array
    {
        $classLikeImports = [];
        $functionImports = [];

        foreach ($this->classLikeImports as $alias => $fqn) {
            $use = $this->use($fqn);

            if (!str_ends_with("\\{$fqn}", "\\{$alias}")) {
                $use->as($alias);
            }

            $classLikeImports[$fqn] = $use->getNode();
        }

        foreach ($this->functionImports as $alias => $fqn) {
            $use = $this->useFunction($fqn);

            if (!str_ends_with("\\{$fqn}", "\\{$alias}")) {
                $use->as($alias);
            }

            $functionImports[$fqn] = $use->getNode();
        }

        ksort($classLikeImports);
        ksort($functionImports);

        return [...array_values($classLikeImports), ...array_values($functionImports)];
    }

}
