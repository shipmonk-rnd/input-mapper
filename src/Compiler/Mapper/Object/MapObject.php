<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper\Object;

use Attribute;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\CompiledExpr;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\UndefinedAwareMapperCompiler;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use function array_fill_keys;
use function array_keys;
use function array_push;
use function count;
use function ucfirst;

/**
 * @template T of object
 */
#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class MapObject implements MapperCompiler
{

    /**
     * @param  class-string<T>               $className
     * @param  array<string, MapperCompiler> $constructorArgsMapperCompilers
     */
    public function __construct(
        public readonly string $className,
        public readonly array $constructorArgsMapperCompilers,
        public readonly bool $allowExtraKeys = false,
    )
    {
    }

    public function compile(Expr $value, Expr $path, PhpCodeBuilder $builder): CompiledExpr
    {
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

        $args = [];

        foreach ($this->constructorArgsMapperCompilers as $key => $argMapperCompiler) {
            $isPresent = $builder->funcCall($builder->importFunction('array_key_exists'), [$builder->val($key), $value]);
            $isMissing = $builder->not($isPresent);

            $propertyValue = $builder->arrayDimFetch($value, $builder->val($key));
            $propertyPath = $builder->arrayImmutableAppend($path, $builder->val($key));
            $propertyMapperMethodName = $builder->uniqMethodName('map' . ucfirst($key));
            $propertyMapperMethod = $builder->mapperMethod($propertyMapperMethodName, $argMapperCompiler)->makePrivate()->getNode();
            $propertyMapperCall = $builder->methodCall($builder->var('this'), $propertyMapperMethodName, [$propertyValue, $propertyPath]);
            $builder->addMethod($propertyMapperMethod);

            if ($argMapperCompiler instanceof UndefinedAwareMapperCompiler) {
                $propertyValueVarName = $builder->uniqVariableName($key);
                $fallbackValueMapper = $argMapperCompiler->compileUndefined($path, $builder->val($key), $builder);

                if (count($fallbackValueMapper->statements) > 0) {
                    $statements[] = $builder->if(
                        $isPresent,
                        [$builder->assign($builder->var($propertyValueVarName), $propertyMapperCall)],
                        [
                            ...$fallbackValueMapper->statements,
                            $builder->assign($builder->var($propertyValueVarName), $fallbackValueMapper->expr),
                        ],
                    );

                    $args[] = $builder->var($propertyValueVarName);
                } else {
                    $args[] = $builder->ternary($isPresent, $propertyMapperCall, $fallbackValueMapper->expr);
                }
            } else {
                $statements[] = $builder->if($isMissing, [
                    $builder->throw(
                        $builder->staticCall(
                            $builder->importClass(MappingFailedException::class),
                            'missingKey',
                            [$path, $key],
                        ),
                    ),
                ]);

                $args[] = $propertyMapperCall;
            }
        }

        if (!$this->allowExtraKeys) {
            array_push($statements, ...$this->checkForExtraKeys($value, $path, $builder));
        }

        return new CompiledExpr(
            $builder->new($builder->importClass($this->className), $args),
            $statements,
        );
    }

    public function getInputType(PhpCodeBuilder $builder): TypeNode
    {
        return new IdentifierTypeNode('mixed');
    }

    public function getOutputType(PhpCodeBuilder $builder): TypeNode
    {
        return new IdentifierTypeNode($builder->importClass($this->className));
    }

    /**
     * @return list<Stmt>
     */
    private function checkForExtraKeys(Expr $value, Expr $path, PhpCodeBuilder $builder): array
    {
        $statements = [];

        $knownKeySet = array_fill_keys(array_keys($this->constructorArgsMapperCompilers), true);
        $knownKeysVariableName = $builder->uniqVariableName('knownKeys');
        $statements[] = $builder->assign($builder->var($knownKeysVariableName), $builder->val($knownKeySet));

        $extraKeysVariableName = $builder->uniqVariableName('extraKeys');
        $statements[] = $builder->assign($builder->var($extraKeysVariableName), $builder->funcCall($builder->importFunction('array_diff_key'), [$value, $builder->var($knownKeysVariableName)]));

        $hasExtraKeys = $builder->gt($builder->funcCall($builder->importFunction('count'), [$builder->var($extraKeysVariableName)]), $builder->val(0));
        $statements[] = $builder->if($hasExtraKeys, [
            $builder->throw(
                $builder->staticCall(
                    $builder->importClass(MappingFailedException::class),
                    'extraKeys',
                    [$path, $builder->funcCall($builder->importFunction('array_keys'), [$builder->var($extraKeysVariableName)])],
                ),
            ),
        ]);

        return $statements;
    }

}
