<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper\Output;

use PhpParser\Node\Expr;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeItemNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\CompiledExpr;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use function ucfirst;

class ArrayShapeOutputMapperCompiler implements MapperCompiler
{

    /**
     * @param list<array{key: string, mapper: MapperCompiler, optional: bool}> $items
     */
    public function __construct(
        public readonly array $items,
        public readonly bool $sealed = true,
    )
    {
    }

    public function compile(
        Expr $value,
        Expr $path,
        PhpCodeBuilder $builder,
    ): CompiledExpr
    {
        $hasOptionalItems = false;

        foreach ($this->items as $itemMapping) {
            if ($itemMapping['optional']) {
                $hasOptionalItems = true;
                break;
            }
        }

        if ($hasOptionalItems) {
            return $this->compileWithOptionalItems($value, $path, $builder);
        }

        $arrayItems = [];

        foreach ($this->items as $itemMapping) {
            $itemValue = $builder->arrayDimFetch($value, $builder->val($itemMapping['key']));
            $mappedItemValue = $this->compileItemValue($itemValue, $path, $itemMapping, $builder);
            $arrayItems[] = $builder->arrayItem($mappedItemValue, $builder->val($itemMapping['key']));
        }

        return new CompiledExpr($builder->array($arrayItems));
    }

    /**
     * @param array{key: string, mapper: MapperCompiler, optional: bool} $itemMapping
     */
    private function compileItemValue(
        Expr $itemValue,
        Expr $path,
        array $itemMapping,
        PhpCodeBuilder $builder,
    ): Expr
    {
        $itemPath = $builder->arrayImmutableAppend($path, $builder->val($itemMapping['key']));
        $itemMapper = $itemMapping['mapper']->compile($itemValue, $itemPath, $builder);

        if ($itemMapper->statements === []) {
            return $itemMapper->expr;
        }

        $itemMapperMethodName = $builder->uniqMethodName('map' . ucfirst($itemMapping['key']));
        $itemMapperMethod = $builder->mapperMethod($itemMapperMethodName, $itemMapping['mapper'])->makePrivate()->getNode();
        $builder->addMethod($itemMapperMethod);

        return $builder->methodCall($builder->var('this'), $itemMapperMethodName, [$itemValue, $itemPath]);
    }

    private function compileWithOptionalItems(
        Expr $value,
        Expr $path,
        PhpCodeBuilder $builder,
    ): CompiledExpr
    {
        $mappedVariableName = $builder->uniqVariableName('mapped');
        $statements = [
            $builder->assign($builder->var($mappedVariableName), $builder->val([])),
        ];

        foreach ($this->items as $itemMapping) {
            $itemValue = $builder->arrayDimFetch($value, $builder->val($itemMapping['key']));
            $mappedItemValue = $this->compileItemValue($itemValue, $path, $itemMapping, $builder);

            $itemAssignment = $builder->assign(
                $builder->arrayDimFetch($builder->var($mappedVariableName), $builder->val($itemMapping['key'])),
                $mappedItemValue,
            );

            if ($itemMapping['optional']) {
                $isPresent = $builder->funcCall($builder->importFunction('array_key_exists'), [$builder->val($itemMapping['key']), $value]);
                $statements[] = $builder->if($isPresent, [$itemAssignment]);
            } else {
                $statements[] = $itemAssignment;
            }
        }

        return new CompiledExpr($builder->var($mappedVariableName), $statements);
    }

    public function getInputType(): TypeNode
    {
        $items = [];

        foreach ($this->items as $mapping) {
            $items[] = new ArrayShapeItemNode(
                new IdentifierTypeNode($mapping['key']),
                $mapping['optional'],
                $mapping['mapper']->getInputType(),
            );
        }

        return $this->sealed
            ? ArrayShapeNode::createSealed($items)
            : ArrayShapeNode::createUnsealed($items, null);
    }

    public function getOutputType(): TypeNode
    {
        $items = [];

        foreach ($this->items as $mapping) {
            $items[] = new ArrayShapeItemNode(
                new IdentifierTypeNode($mapping['key']),
                $mapping['optional'],
                $mapping['mapper']->getOutputType(),
            );
        }

        return $this->sealed
            ? ArrayShapeNode::createSealed($items)
            : ArrayShapeNode::createUnsealed($items, null);
    }

}
