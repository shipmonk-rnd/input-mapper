<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Attribute;

use Attribute;
use ShipMonk\InputMapper\Compiler\Mapper\Input\ArrayShapeInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompilerProvider;
use ShipMonk\InputMapper\Compiler\Mapper\Output\ArrayShapeOutputMapperCompiler;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class MapArrayShape implements MapperCompilerProvider
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

    public function getInputMapperCompiler(): MapperCompiler
    {
        $compilerItems = [];

        foreach ($this->items as $item) {
            $compilerItems[] = ['key' => $item->key, 'mapper' => $item->mapper->getInputMapperCompiler(), 'optional' => $item->optional];
        }

        return new ArrayShapeInputMapperCompiler($compilerItems, $this->sealed);
    }

    public function getOutputMapperCompiler(): MapperCompiler
    {
        $compilerItems = [];

        foreach ($this->items as $item) {
            $compilerItems[] = ['key' => $item->key, 'mapper' => $item->mapper->getOutputMapperCompiler(), 'optional' => $item->optional];
        }

        return new ArrayShapeOutputMapperCompiler($compilerItems, $this->sealed);
    }

}
