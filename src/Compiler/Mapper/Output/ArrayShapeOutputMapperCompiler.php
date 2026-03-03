<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper\Output;

use PhpParser\Node\Expr;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeItemNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\Attribute\ArrayShapeItemMapping;
use ShipMonk\InputMapper\Compiler\CompiledExpr;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use function ucfirst;

class ArrayShapeOutputMapperCompiler implements MapperCompiler
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

    public function compile(
        Expr $value,
        Expr $path,
        PhpCodeBuilder $builder,
    ): CompiledExpr
    {
        $statements = [];
        $mappedVariableName = $builder->uniqVariableName('mapped');

        $statements[] = $builder->assign($builder->var($mappedVariableName), $builder->val([]));

        foreach ($this->items as $itemMapping) {
            $itemValue = $builder->arrayDimFetch($value, $builder->val($itemMapping->key));
            $itemPath = $builder->arrayImmutableAppend($path, $builder->val($itemMapping->key));
            $itemMapperMethodName = $builder->uniqMethodName('map' . ucfirst($itemMapping->key));
            $itemMapperMethod = $builder->outputMapperMethod($itemMapperMethodName, $itemMapping->mapper)->makePrivate()->getNode();
            $builder->addMethod($itemMapperMethod);

            $itemAssignment = $builder->assign(
                $builder->arrayDimFetch($builder->var($mappedVariableName), $builder->val($itemMapping->key)),
                $builder->methodCall($builder->var('this'), $itemMapperMethodName, [$itemValue, $itemPath]),
            );

            if ($itemMapping->optional) {
                $isPresent = $builder->funcCall($builder->importFunction('array_key_exists'), [$builder->val($itemMapping->key), $value]);
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
                new IdentifierTypeNode($mapping->key),
                $mapping->optional,
                $mapping->mapper->getInputType(),
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
                new IdentifierTypeNode($mapping->key),
                $mapping->optional,
                $mapping->mapper->getOutputType(),
            );
        }

        return $this->sealed
            ? ArrayShapeNode::createSealed($items)
            : ArrayShapeNode::createUnsealed($items, null);
    }

}
