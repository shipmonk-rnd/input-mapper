<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Object;

use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Object\MapDiscriminatedObject;
use ShipMonk\InputMapper\Compiler\Mapper\Object\MapEnum;
use ShipMonk\InputMapper\Compiler\Mapper\Object\MapObject;
use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapInt;
use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapString;
use ShipMonk\InputMapper\Compiler\Mapper\Wrapper\MapOptional;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Optional;
use ShipMonkTests\InputMapper\Compiler\Mapper\MapperCompilerTestCase;
use ShipMonkTests\InputMapper\Compiler\Mapper\Object\Data\HierarchicalChildOneInput;
use ShipMonkTests\InputMapper\Compiler\Mapper\Object\Data\HierarchicalChildTwoInput;
use ShipMonkTests\InputMapper\Compiler\Mapper\Object\Data\HierarchicalParentInput;
use ShipMonkTests\InputMapper\Compiler\Mapper\Object\Data\HierarchicalWithEnumChildInput;
use ShipMonkTests\InputMapper\Compiler\Mapper\Object\Data\HierarchicalWithEnumParentInput;
use ShipMonkTests\InputMapper\Compiler\Mapper\Object\Data\HierarchicalWithEnumType;

class MapDiscriminatedObjectTest extends MapperCompilerTestCase
{

    public function testCompile(): void
    {
        $parentInputMapper = $this->compileMapper('HierarchicalParentInput', $this->createParentInputMapperCompiler());

        $childOneInputObject = new HierarchicalChildOneInput(
            id: 1,
            name: 'John Doe',
            age: Optional::of(30),
            type: 'childOne',
            childOneField: 'childOneField',
        );

        $childOneInputArray = [
            'id' => 1,
            'name' => 'John Doe',
            'type' => 'childOne',
            'age' => 30,
            'childOneField' => 'childOneField',
        ];

        self::assertEquals($childOneInputObject, $parentInputMapper->map($childOneInputArray));

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected array, got null',
            static fn() => $parentInputMapper->map(null),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected array, got 123',
            static fn() => $parentInputMapper->map(123),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Unrecognized key "extra"',
            static fn() => $parentInputMapper->map($childOneInputArray + ['extra' => 1]),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /type: Expected one of childOne, childTwo, got null',
            static fn() => $parentInputMapper->map([...$childOneInputArray, 'type' => null]),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /type: Expected one of childOne, childTwo, got "c"',
            static fn() => $parentInputMapper->map([...$childOneInputArray, 'type' => 'c']),
        );

        $childOneInputWithoutType = $childOneInputArray;
        unset($childOneInputWithoutType['type']);

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Missing required key "type"',
            static fn() => $parentInputMapper->map($childOneInputWithoutType),
        );

        $childTwoInputObject = new HierarchicalChildTwoInput(
            id: 1,
            name: 'John Doe',
            age: Optional::of(30),
            type: 'childTwo',
            childTwoField: 5,
        );

        $childTwoInputArray = [
            'id' => 1,
            'name' => 'John Doe',
            'type' => 'childTwo',
            'age' => 30,
            'childTwoField' => 5,
        ];

        self::assertEquals($childTwoInputObject, $parentInputMapper->map($childTwoInputArray));
    }

    public function testCompileWithEnumAsType(): void
    {
        $parentInputMapper = $this->compileMapper('HierarchicalWithEnumParentInput', $this->createParentInputWithEnumMapperCompiler());

        $childOneInputObject = new HierarchicalWithEnumChildInput(
            id: 1,
            type: HierarchicalWithEnumType::ChildOne,
        );

        $childOneInputArray = [
            'id' => 1,
            'type' => 'childOne',
        ];

        self::assertEquals($childOneInputObject, $parentInputMapper->map($childOneInputArray));

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /type: Expected one of childOne, got null',
            static fn() => $parentInputMapper->map([...$childOneInputArray, 'type' => null]),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /type: Expected one of childOne, got "c"',
            static fn() => $parentInputMapper->map([...$childOneInputArray, 'type' => 'c']),
        );
    }

    private function createParentInputMapperCompiler(): MapperCompiler
    {
        return new MapDiscriminatedObject(
            HierarchicalParentInput::class,
            new MapString(),
            'type',
            [
                'childOne' => new MapObject(HierarchicalChildOneInput::class, [
                    'id' => new MapInt(),
                    'name' => new MapString(),
                    'age' => new MapOptional(new MapInt()),
                    'type' => new MapString(),
                    'childOneField' => new MapString(),
                ]),
                'childTwo' => new MapObject(HierarchicalChildTwoInput::class, [
                    'id' => new MapInt(),
                    'name' => new MapString(),
                    'age' => new MapOptional(new MapInt()),
                    'type' => new MapString(),
                    'childTwoField' => new MapInt(),
                ]),
            ],
        );
    }

    private function createParentInputWithEnumMapperCompiler(): MapperCompiler
    {
        return new MapDiscriminatedObject(
            HierarchicalWithEnumParentInput::class,
            new MapString(),
            'type',
            [
                'childOne' => new MapObject(HierarchicalWithEnumChildInput::class, [
                    'id' => new MapInt(),
                    'type' => new MapEnum(HierarchicalWithEnumType::class, new MapString()),
                ]),
            ],
        );
    }

}
