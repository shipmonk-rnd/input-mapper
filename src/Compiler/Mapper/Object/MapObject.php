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
use ShipMonk\InputMapper\Runtime\MappingFailedException;
use function array_fill_keys;
use function array_map;
use function array_push;
use function count;
use function ucfirst;

/**
 * @template T of object
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class MapObject implements MapperCompiler
{

    /**
     * @param class-string<T>       $className
     * @param list<PropertyMapping> $properties
     */
    public function __construct(
        public readonly string $className,
        public readonly array $properties,
        public readonly bool $allowExtraProperties = false,
    )
    {
    }

    public function compile(Expr $value, Expr $path, PhpCodeBuilder $builder): CompiledExpr
    {
        $statements = [
            $builder->if($builder->not($builder->funcCall($builder->importFunction('is_array'), [$value])), [
                $builder->throwNew($builder->importClass(MappingFailedException::class), [
                    $value,
                    $path,
                    $builder->val('array'),
                ]),
            ]),
        ];

        $args = [];

        foreach ($this->properties as $propertyMapping) {
            $isPresent = $builder->funcCall($builder->importFunction('array_key_exists'), [$builder->val($propertyMapping->name), $value]);
            $isMissing = $builder->not($isPresent);

            $propertyValue = $builder->arrayDimFetch($value, $builder->val($propertyMapping->name));
            $propertyPath = $builder->arrayImmutableAppend($path, $builder->val($propertyMapping->name));
            $propertyMapperMethodName = $builder->uniqMethodName('map' . ucfirst($propertyMapping->name));
            $propertyMapperMethod = $builder->mapperMethod($propertyMapperMethodName, $propertyMapping->mapper)->makePrivate()->getNode();
            $propertyMapperCall = $builder->methodCall($builder->var('this'), $propertyMapperMethodName, [$propertyValue, $propertyPath]);
            $builder->addMethod($propertyMapperMethod);

            if ($propertyMapping->optional) {
                if ($propertyMapping->mapper instanceof UndefinedAwareMapperCompiler) {
                    $propertyValueVarName = $builder->uniqVariableName($propertyMapping->name);
                    $fallbackValueMapper = $propertyMapping->mapper->compileUndefined($path, $builder);

                    if (count($fallbackValueMapper->statements) > 0) {
                        $statements[] = $builder->if(
                            $isPresent,
                            [$builder->assign($builder->var($propertyValueVarName), $propertyMapperCall)],
                            [...$fallbackValueMapper->statements, $builder->assign($builder->var($propertyValueVarName), $fallbackValueMapper->expr)],
                        );

                        $args[] = $builder->var($propertyValueVarName);
                    } else {
                        $args[] = $builder->ternary($isPresent, $propertyMapperCall, $fallbackValueMapper->expr);
                    }
                } else {
                    $args[] = $builder->ternary($isPresent, $propertyMapperCall, $builder->val(null));
                }
            } else {
                $statements[] = $builder->if($isMissing, [
                    $builder->throwNew($builder->importClass(MappingFailedException::class), [
                        $value,
                        $path,
                        $builder->val("property {$propertyMapping->name} to exist"),
                    ]),
                ]);

                $args[] = $propertyMapperCall;
            }
        }

        if (!$this->allowExtraProperties) {
            array_push($statements, ...$this->checkForExtraKeys($value, $path, $builder));
        }

        return new CompiledExpr(
            $builder->new($builder->importClass($this->className), $args),
            $statements,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function getJsonSchema(): array
    {
        $propertySchemas = [];
        $required = [];

        foreach ($this->properties as $propertyMapping) {
            $propertySchemas[$propertyMapping->name] = $propertyMapping->mapper->getJsonSchema();

            if (!$propertyMapping->optional) {
                $required[] = $propertyMapping->name;
            }
        }

        return [
            'type' => 'object',
            'properties' => $propertySchemas,
            'required' => $required,
        ];
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

        $knownKeySet = array_fill_keys(array_map(static fn (PropertyMapping $mapping) => $mapping->name, $this->properties), true);
        $knownKeysVariableName = $builder->uniqVariableName('knownKeys');
        $statements[] = $builder->assign($builder->var($knownKeysVariableName), $builder->val($knownKeySet));

        $extraKeysVariableName = $builder->uniqVariableName('extraKeys');
        $statements[] = $builder->assign($builder->var($extraKeysVariableName), $builder->funcCall($builder->importFunction('array_diff_key'), [$value, $builder->var($knownKeysVariableName)]));

        $hasExtraKeys = $builder->gt($builder->funcCall($builder->importFunction('count'), [$builder->var($extraKeysVariableName)]), $builder->val(0));
        $statements[] = $builder->if($hasExtraKeys, [
            $builder->throwNew($builder->importClass(MappingFailedException::class), [
                $value,
                $path,
                $builder->concat(
                    $builder->val('have only the keys '),
                    $builder->funcCall($builder->importFunction('implode'), [$builder->val(', '), $builder->var($knownKeysVariableName)]),
                    $builder->val(', but got '),
                    $builder->funcCall($builder->importFunction('implode'), [$builder->val(', '), $builder->var($extraKeysVariableName)]),
                    $builder->val(' as well'),
                ),
            ]),
        ]);

        return $statements;
    }

}
