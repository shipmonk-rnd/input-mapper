<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Php;

use LogicException;
use Nette\Utils\Arrays;
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
use PhpParser\Node\Expr\BinaryOp\Plus;
use PhpParser\Node\Expr\BinaryOp\Smaller;
use PhpParser\Node\Expr\BinaryOp\SmallerOrEqual;
use PhpParser\Node\Expr\BooleanNot;
use PhpParser\Node\Expr\Instanceof_;
use PhpParser\Node\Expr\PreInc;
use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Class_ as ClassNode;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Declare_;
use PhpParser\Node\Stmt\DeclareDeclare;
use PhpParser\Node\Stmt\Else_;
use PhpParser\Node\Stmt\ElseIf_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\For_;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\Nop;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Stmt\Throw_;
use PhpParser\Node\Stmt\Use_;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeItemNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ObjectShapeItemNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\CompiledExpr;
use ShipMonk\InputMapper\Compiler\Mapper\GenericMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Type\GenericTypeParameter;
use ShipMonk\InputMapper\Compiler\Type\PhpDocTypeUtils;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use function array_column;
use function array_fill_keys;
use function array_filter;
use function array_pop;
use function array_slice;
use function array_values;
use function assert;
use function count;
use function get_object_vars;
use function implode;
use function is_array;
use function is_object;
use function ksort;
use function serialize;
use function str_ends_with;
use function strrpos;
use function substr;
use function unserialize;

class PhpCodeBuilder extends BuilderFactory
{

    /**
     * @var list<GenericTypeParameter>
     */
    private array $genericParameters = [];

    /**
     * @var array<string, string> alias => class like FQN
     */
    private array $classLikeImports = [];

    /**
     * @var array<string, string> alias => function FQN
     */
    private array $functionImports = [];

    /**
     * @var array<string, Expr|bool|int|float|string|array<mixed>|null>
     */
    private array $constants = [];

    /**
     * @var array<string, ClassMethod>
     */
    private array $methods = [];

    /**
     * @var array<int, array<string, bool>>
     */
    private array $variables = [];

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

    public function arrayDimFetch(Expr $var, ?Expr $dim = null): ArrayDimFetch
    {
        return new ArrayDimFetch($var, $dim);
    }

    public function not(Expr $expr): BooleanNot
    {
        return new BooleanNot($expr);
    }

    public function and(Expr $operand, Expr ...$rest): Expr
    {
        return count($rest) === 0
            ? $operand
            : new BooleanAnd($this->and($operand, ...array_slice($rest, 0, -1)), array_slice($rest, -1)[0]);
    }

    public function or(Expr $operand, Expr ...$rest): Expr
    {
        return count($rest) === 0
            ? $operand
            : new BooleanOr($this->or($operand, ...array_slice($rest, 0, -1)), array_slice($rest, -1)[0]);
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

    public function instanceOf(Expr $left, string $right): Instanceof_
    {
        return new Instanceof_($left, new Name($right));
    }

    public function ternary(Expr $cond, Expr $ifTrue, Expr $else): Ternary
    {
        return new Ternary($cond, $ifTrue, $else);
    }

    /**
     * @param list<Stmt> $then
     * @param list<Stmt>|null $else
     */
    public function if(Expr $if, array $then, ?array $else = null): If_
    {
        $elseIfClauses = [];
        $elseClause = null;

        while ($else !== null && count($else) === 1 && $else[0] instanceof If_ && count($else[0]->elseifs) === 0) {
            $elseIfClauses[] = new ElseIf_($else[0]->cond, $else[0]->stmts);
            $else = $else[0]->else?->stmts;
        }

        if ($else !== null) {
            $elseClause = new Else_($else);
        }

        return new If_($if, ['stmts' => $then, 'elseifs' => $elseIfClauses, 'else' => $elseClause]);
    }

    /**
     * @param list<Stmt> $statements
     */
    public function foreach(Expr $expr, Expr $value, Expr $key, array $statements): Foreach_
    {
        return new Foreach_($expr, $value, ['stmts' => $statements, 'keyVar' => $key]);
    }

    /**
     * @param list<Stmt> $statements
     */
    public function for(Expr $init, Expr $cond, Expr $loop, array $statements): For_
    {
        return new For_([
            'init' => [$init],
            'cond' => [$cond],
            'loop' => [$loop],
            'stmts' => $statements,
        ]);
    }

    public function preIncrement(Expr $var): PreInc
    {
        return new PreInc($var, []);
    }

    public function plus(Expr $var, Expr $value): Plus
    {
        return new Plus($var, $value);
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

    public function assign(Expr $var, Expr $expr): Expression
    {
        return new Expression(new Assign($var, $expr));
    }

    public function assignExpr(Expr $var, Expr $expr): Assign
    {
        return new Assign($var, $expr);
    }

    public function return(Expr $expr): Return_
    {
        return new Return_($expr);
    }

    public function uniqConstantName(string $name, mixed $value): string
    {
        $i = 1;
        $uniqueName = $name;

        while (isset($this->constants[$uniqueName]) && $this->constants[$uniqueName] !== $value) {
            $uniqueName = $name . ++$i;
        }

        return $uniqueName;
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
     * @param callable(): T $cb
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

    /**
     * @param Expr|bool|int|float|string|array<mixed>|null $value
     */
    public function addConstant(string $name, Expr|bool|null|int|float|string|array $value): void
    {
        if (isset($this->constants[$name]) && $this->constants[$name] !== $value) {
            throw new LogicException('Constant already exists with different value');
        }

        $this->constants[$name] = $value;
    }

    public function addMethod(ClassMethod $method): void
    {
        if (isset($this->methods[$method->name->name])) {
            throw new LogicException('Method already exists');
        }

        $this->methods[$method->name->name] = $method;
    }

    public function importClass(string $className): string
    {
        $lastBackslashOffset = strrpos($className, '\\');
        $shortName = $lastBackslashOffset === false ? $className : substr($className, $lastBackslashOffset + 1);

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

    public function importType(TypeNode $type): void
    {
        $stack = [$type];
        $index = 1;

        while ($index > 0) {
            $value = $stack[--$index];

            if ($value instanceof IdentifierTypeNode) {
                if (!PhpDocTypeUtils::isKeyword($value)) {
                    /** @var class-string $className allow-narrowing */
                    $className = $value->name;
                    $value->name = $this->importClass($className);
                }
            } elseif ($value instanceof ArrayShapeItemNode || $value instanceof ObjectShapeItemNode) {
                $stack[$index++] = $value->valueType; // intentionally not pushing $value->keyName

            } else {
                foreach (is_array($value) ? $value : get_object_vars($value) as $item) {
                    if (is_array($item) || is_object($item)) {
                        $stack[$index++] = $item;
                    }
                }
            }
        }
    }

    /**
     * @param list<?string> $lines
     */
    public function phpDoc(array $lines): string
    {
        $lines = array_filter($lines, static fn(?string $line): bool => $line !== null);

        if (count($lines) === 0) {
            return '';
        }

        return "/**\n * " . implode("\n * ", $lines) . "\n */";
    }

    public function mapperMethod(string $methodName, MapperCompiler $mapperCompiler): Method
    {
        $mapper = $this->withVariableScope(function () use ($mapperCompiler, &$dataVarName, &$pathVarName): CompiledExpr {
            [$dataVarName, $pathVarName] = $this->uniqVariableNames('data', 'path');
            return $mapperCompiler->compile($this->var($dataVarName), $this->var($pathVarName), $this);
        });

        assert($dataVarName !== null && $pathVarName !== null);
        $inputType = $mapperCompiler->getInputType();
        $outputType = $mapperCompiler->getOutputType();

        $this->importType($inputType);
        $this->importType($outputType);

        $clonedGenericParameters = [];

        foreach ($this->genericParameters as $genericParameter) {
            /** @var GenericTypeParameter $clonedGenericParameter */
            $clonedGenericParameter = unserialize(serialize($genericParameter));
            $clonedGenericParameters[] = $clonedGenericParameter;

            if ($clonedGenericParameter->bound !== null) {
                $this->importType($clonedGenericParameter->bound);
            }
        }

        $nativeInputType = PhpDocTypeUtils::toNativeType($inputType, $clonedGenericParameters, $phpDocInputTypeUseful);
        $nativeOutputType = PhpDocTypeUtils::toNativeType($outputType, $clonedGenericParameters, $phpDocOutputTypeUseful);

        $phpDoc = $this->phpDoc([
            $phpDocInputTypeUseful !== false ? "@param  {$inputType} \${$dataVarName}" : null,
            "@param  list<string|int> \${$pathVarName}",
            $phpDocOutputTypeUseful !== false ? "@return {$outputType}" : null,
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

    public function mapperClassConstructor(MapperCompiler $mapperCompiler): ClassMethod
    {
        $mapperConstructorPhpDocLines = [];
        $mapperConstructorBuilder = $this->method('__construct');

        $providerParameter = $this->param('provider')->setType($this->importClass(MapperProvider::class))->getNode();
        $providerParameter->flags = ClassNode::MODIFIER_PRIVATE | ClassNode::MODIFIER_READONLY;
        $mapperConstructorBuilder->addParam($providerParameter);

        if ($mapperCompiler instanceof GenericMapperCompiler && count($mapperCompiler->getGenericParameters()) > 0) {
            $innerMappersParameter = $this->param('innerMappers')->setType('array')->getNode();
            $innerMappersParameter->flags = ClassNode::MODIFIER_PRIVATE | ClassNode::MODIFIER_READONLY;
            $mapperConstructorBuilder->addParam($innerMappersParameter);

            $innerMappersType = new ArrayShapeNode(Arrays::map(
                $mapperCompiler->getGenericParameters(),
                static function (GenericTypeParameter $genericParameter): ArrayShapeItemNode {
                    return new ArrayShapeItemNode(
                        keyName: null,
                        valueType: new GenericTypeNode(new IdentifierTypeNode(Mapper::class), [new IdentifierTypeNode($genericParameter->name)]),
                        optional: false,
                    );
                },
            ));

            $this->importType($innerMappersType);
            $mapperConstructorPhpDocLines[] = "@param {$innerMappersType} \$innerMappers";
        }

        return $mapperConstructorBuilder
            ->makePublic()
            ->setDocComment($this->phpDoc($mapperConstructorPhpDocLines))
            ->getNode();
    }

    public function mapperClass(string $shortClassName, MapperCompiler $mapperCompiler): Class_
    {
        $mapperConstructor = $this->mapperClassConstructor($mapperCompiler);

        $mapMethod = $this->mapperMethod('map', $mapperCompiler)
            ->makePublic()
            ->getNode();

        $outputType = $mapperCompiler->getOutputType();
        $this->importType($outputType);

        $mapperCompilerType = $this->importClass($mapperCompiler::class);

        $implementsType = new GenericTypeNode(
            new IdentifierTypeNode($this->importClass(Mapper::class)),
            [$outputType],
        );

        $phpDocLines = [
            "Generated mapper by {@see $mapperCompilerType}. Do not edit directly.",
            '',
        ];

        if ($mapperCompiler instanceof GenericMapperCompiler) {
            foreach ($mapperCompiler->getGenericParameters() as $genericParameter) {
                $phpDocLines[] = $genericParameter->toPhpDocLine();
            }
        }

        $phpDocLines[] = "@implements {$implementsType}";
        $phpDoc = $this->phpDoc($phpDocLines);

        $constants = Arrays::map(
            $this->constants,
            function (Expr|bool|null|int|float|string|array $value, string $name): ClassConst {
                return $this->classConst($name, $value)
                    ->makePrivate()
                    ->getNode();
            },
        );

        return $this->class($shortClassName)
            ->setDocComment($phpDoc)
            ->implement($this->importClass(Mapper::class))
            ->addStmts($constants)
            ->addStmt($mapperConstructor)
            ->addStmt($mapMethod)
            ->addStmts(array_values($this->methods));
    }

    /**
     * @return list<Stmt>
     */
    public function mapperFile(string $mapperClassName, MapperCompiler $mapperCompiler): array
    {
        $pos = strrpos($mapperClassName, '\\');
        $namespaceName = $pos === false ? '' : substr($mapperClassName, 0, $pos);
        $shortClassName = $pos === false ? $mapperClassName : substr($mapperClassName, $pos + 1);

        if ($mapperCompiler instanceof GenericMapperCompiler) {
            $this->genericParameters = $mapperCompiler->getGenericParameters();
        }

        $mapperClass = $this->mapperClass($shortClassName, $mapperCompiler)
            ->getNode();

        return $this->file($namespaceName, [$mapperClass]);
    }

    /**
     * @param list<Stmt> $statements
     * @return list<Stmt>
     */
    public function file(string $namespaceName, array $statements): array
    {
        $statements = [
            ...$this->getImports($namespaceName),
            ...$statements,
        ];

        if ($namespaceName !== '') {
            $statements = [
                $this->namespace($namespaceName)
                    ->addStmts($statements)
                    ->getNode(),
            ];
        }

        return [
            new Declare_([new DeclareDeclare('strict_types', $this->val(1))]),
            new Nop(),
            ...$statements,
        ];
    }

    /**
     * @return list<GenericTypeParameter>
     */
    public function getGenericParameters(): array
    {
        return $this->genericParameters;
    }

    /**
     * @return list<Use_>
     */
    public function getImports(string $namespace): array
    {
        $genericParameterNames = array_fill_keys(array_column($this->genericParameters, 'name'), true);
        $classLikeImports = [];
        $functionImports = [];

        foreach ($this->classLikeImports as $alias => $fqn) {
            $use = $this->use($fqn);

            if (!str_ends_with("\\{$fqn}", "\\{$alias}")) {
                $use->as($alias);

            } elseif ($fqn === "{$namespace}\\{$alias}") {
                continue;

            } elseif (isset($genericParameterNames[$alias])) {
                continue;
            }

            $classLikeImports[$fqn] = $use->getNode();
        }

        foreach ($this->functionImports as $alias => $fqn) {
            $use = $this->useFunction($fqn);

            if (!str_ends_with("\\{$fqn}", "\\{$alias}")) {
                $use->as($alias);

            } elseif ($fqn === "{$namespace}\\{$alias}") {
                continue;
            }

            $functionImports[$fqn] = $use->getNode();
        }

        ksort($classLikeImports);
        ksort($functionImports);

        return [...array_values($classLikeImports), ...array_values($functionImports)];
    }

}
