<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper\Array;

use Attribute;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprStringNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeItemNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\CompiledExpr;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use function array_fill_keys;
use function array_map;
use function array_push;
use function ucfirst;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class MapArrayShape implements MapperCompiler
{

    /**
     * @param list<ArrayShapeItemMapping> $items
     */
    public function __construct(
        public readonly array $items,
        public readonly bool $sealed = true,
    )
    {
    }

    public function compile(Expr $value, Expr $context, PhpCodeBuilder $builder): CompiledExpr
    {
        $statements = [];
        $mappedVariableName = $builder->uniqVariableName('mapped');

        $isNotArray = $builder->not($builder->funcCall($builder->importFunction('is_array'), [$value]));
        $statements[] = $builder->if($isNotArray, [
            $builder->throw(
                $builder->staticCall(
                    $builder->importClass(MappingFailedException::class),
                    'incorrectType',
                    [$value, $context, $builder->val('array')],
                ),
            ),
        ]);

        $statements[] = $builder->assign($builder->var($mappedVariableName), $builder->val([]));

        foreach ($this->items as $itemMapping) {
            $isPresent = $builder->funcCall($builder->importFunction('array_key_exists'), [$builder->val($itemMapping->key), $value]);
            $isMissing = $builder->not($isPresent);

            $itemValue = $builder->arrayDimFetch($value, $builder->val($itemMapping->key));
            $itemContext = $builder->mapperContextAppend($context, $builder->val($itemMapping->key));
            $itemMapperMethodName = $builder->uniqMethodName('map' . ucfirst($itemMapping->key));
            $itemMapperMethod = $builder->mapperMethod($itemMapperMethodName, $itemMapping->mapper)->makePrivate()->getNode();
            $builder->addMethod($itemMapperMethod);

            $itemAssignment = $builder->assign(
                $builder->arrayDimFetch($builder->var($mappedVariableName), $builder->val($itemMapping->key)),
                $builder->methodCall($builder->var('this'), $itemMapperMethodName, [$itemValue, $itemContext]),
            );

            if ($itemMapping->optional) {
                $statements[] = $builder->if($isPresent, [$itemAssignment]);

            } else {
                $statements[] = $builder->if($isMissing, [
                    $builder->throw(
                        $builder->staticCall(
                            $builder->importClass(MappingFailedException::class),
                            'missingKey',
                            [$context, $builder->val($itemMapping->key)],
                        ),
                    ),
                ]);

                $statements[] = $itemAssignment;
            }
        }

        if ($this->sealed) {
            array_push($statements, ...$this->checkForExtraKeys($value, $context, $builder));
        }

        return new CompiledExpr($builder->var($mappedVariableName), $statements);
    }

    public function getInputType(): TypeNode
    {
        return new IdentifierTypeNode('mixed');
    }

    public function getOutputType(): TypeNode
    {
        $items = [];

        foreach ($this->items as $mapping) {
            $items[] = new ArrayShapeItemNode(
                new ConstExprStringNode($mapping->key),
                $mapping->optional,
                $mapping->mapper->getOutputType(),
            );
        }

        return new ArrayShapeNode($items, $this->sealed, ArrayShapeNode::KIND_ARRAY);
    }

    /**
     * @return list<Stmt>
     */
    private function checkForExtraKeys(Expr $value, Expr $context, PhpCodeBuilder $builder): array
    {
        $statements = [];

        $knownKeySet = array_fill_keys(array_map(static fn (ArrayShapeItemMapping $mapping) => $mapping->key, $this->items), true);
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
                    [$context, $builder->funcCall($builder->importFunction('array_keys'), [$builder->var($extraKeysVariableName)])],
                ),
            ),
        ]);

        return $statements;
    }

}
