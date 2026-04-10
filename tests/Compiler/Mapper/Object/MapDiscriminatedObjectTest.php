<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Object;

use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use ShipMonk\InputMapper\Compiler\Exception\CannotCompileMapperException;
use ShipMonk\InputMapper\Compiler\Mapper\Input\DelegateInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Input\DiscriminatedObjectInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Input\EnumInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Input\IntInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Input\ObjectInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Input\OptionalInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Input\StringInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Type\GenericTypeParameter;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\Optional;
use ShipMonk\InputMapperTests\Compiler\Mapper\MapperCompilerTestCase;
use ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data\HierarchicalChildOneInput;
use ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data\HierarchicalChildTwoInput;
use ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data\HierarchicalParentInput;
use ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data\HierarchicalWithEnumChildInput;
use ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data\HierarchicalWithEnumParentInput;
use ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data\HierarchicalWithEnumType;
use ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data\HierarchicalWithNoTypeFieldChildInput;
use ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data\HierarchicalWithNoTypeFieldParentInput;
use ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data\MovieInput;

class MapDiscriminatedObjectTest extends MapperCompilerTestCase
{

    public function testCompile(): void
    {
        $parentInputMapper = $this->compileInputMapper('HierarchicalParentInput', $this->createParentInputMapperCompiler(), [
            HierarchicalChildOneInput::class => $this->createHierarchicalChildOneInputMapperCompiler(),
            HierarchicalChildTwoInput::class => $this->createHierarchicalChildTwoInputMapperCompiler(),
        ]);

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
            static fn () => $parentInputMapper->map(null),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected array, got 123',
            static fn () => $parentInputMapper->map(123),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Unrecognized key "extra"',
            static fn () => $parentInputMapper->map($childOneInputArray + ['extra' => 1]),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /type: Expected one of childOne, childTwo, got null',
            static fn () => $parentInputMapper->map([...$childOneInputArray, 'type' => null]),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /type: Expected one of childOne, childTwo, got "c"',
            static fn () => $parentInputMapper->map([...$childOneInputArray, 'type' => 'c']),
        );

        $childOneInputWithoutType = $childOneInputArray;
        unset($childOneInputWithoutType['type']);

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Missing required key "type"',
            static fn () => $parentInputMapper->map($childOneInputWithoutType),
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
        $parentInputMapper = $this->compileInputMapper('HierarchicalWithEnumParentInput', $this->createParentInputWithEnumMapperCompiler(), [
            HierarchicalWithEnumChildInput::class => $this->createHierarchicalChildWithEnumMapperCompiler(),
        ]);

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
            static fn () => $parentInputMapper->map([...$childOneInputArray, 'type' => null]),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /type: Expected one of childOne, got "c"',
            static fn () => $parentInputMapper->map([...$childOneInputArray, 'type' => 'c']),
        );
    }

    public function testCompileWithNoTypeFieldMapping(): void
    {
        $parentInputMapper = $this->compileInputMapper('HierarchicalWithNoTypeFieldInput', $this->createParentInputWithNoTypeFieldMapperCompiler(), [
            HierarchicalWithNoTypeFieldChildInput::class => $this->createHierarchicalChildWithNoTypeFieldMapperCompiler(),
        ]);

        $childOneInputObject = new HierarchicalWithNoTypeFieldChildInput(
            id: 1,
            childOneField: 'abc',
        );

        $childOneInputArray = [
            'id' => 1,
            '$type' => 'childOne',
            'childOneField' => 'abc',
        ];

        self::assertEquals($childOneInputObject, $parentInputMapper->map($childOneInputArray));

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /$type: Expected one of childOne, got null',
            static fn () => $parentInputMapper->map([...$childOneInputArray, '$type' => null]),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /$type: Expected one of childOne, got "c"',
            static fn () => $parentInputMapper->map([...$childOneInputArray, '$type' => 'c']),
        );
    }

    public function testGetOutputTypeWithGenericParameters(): void
    {
        $mapperCompiler = new DiscriminatedObjectInputMapperCompiler(
            HierarchicalParentInput::class,
            'type',
            [
                'childOne' => new DelegateInputMapperCompiler(HierarchicalChildOneInput::class),
            ],
            genericParameters: [
                new GenericTypeParameter('T'),
            ],
        );

        self::assertEquals(
            new GenericTypeNode(
                new IdentifierTypeNode(HierarchicalParentInput::class),
                [new IdentifierTypeNode('T')],
            ),
            $mapperCompiler->getOutputType(),
        );
    }

    public function testCompileWithSubtypesFromDifferentHierarchies(): void
    {
        $mapperCompiler = new DiscriminatedObjectInputMapperCompiler(
            HierarchicalParentInput::class,
            'type',
            [
                'childOne' => new DelegateInputMapperCompiler(HierarchicalChildOneInput::class),
                'childTwo' => new DelegateInputMapperCompiler(MovieInput::class),
            ],
        );

        self::assertException(
            CannotCompileMapperException::class,
            'Cannot compile mapper ShipMonk\InputMapper\Compiler\Mapper\Input\DelegateInputMapperCompiler as subtype (#[Discriminator]) mapper, because its output type \'ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data\MovieInput\' is not subtype of \'ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data\HierarchicalParentInput\'',
            fn (): Mapper => $this->compileInputMapper('InvalidHierarchyMapper', $mapperCompiler),
        );
    }

    private function createParentInputMapperCompiler(): MapperCompiler
    {
        return new DiscriminatedObjectInputMapperCompiler(
            HierarchicalParentInput::class,
            'type',
            [
                'childOne' => new DelegateInputMapperCompiler(HierarchicalChildOneInput::class),
                'childTwo' => new DelegateInputMapperCompiler(HierarchicalChildTwoInput::class),
            ],
        );
    }

    private function createParentInputWithEnumMapperCompiler(): MapperCompiler
    {
        return new DiscriminatedObjectInputMapperCompiler(
            HierarchicalWithEnumParentInput::class,
            'type',
            [
                'childOne' => new DelegateInputMapperCompiler(HierarchicalWithEnumChildInput::class),
            ],
        );
    }

    public function createHierarchicalChildOneInputMapperCompiler(): MapperCompiler
    {
        return new ObjectInputMapperCompiler(HierarchicalChildOneInput::class, [
            'id' => new IntInputMapperCompiler(),
            'name' => new StringInputMapperCompiler(),
            'age' => new OptionalInputMapperCompiler(new IntInputMapperCompiler()),
            'type' => new StringInputMapperCompiler(),
            'childOneField' => new StringInputMapperCompiler(),
        ]);
    }

    public function createHierarchicalChildTwoInputMapperCompiler(): MapperCompiler
    {
        return new ObjectInputMapperCompiler(HierarchicalChildTwoInput::class, [
            'id' => new IntInputMapperCompiler(),
            'name' => new StringInputMapperCompiler(),
            'age' => new OptionalInputMapperCompiler(new IntInputMapperCompiler()),
            'type' => new StringInputMapperCompiler(),
            'childTwoField' => new IntInputMapperCompiler(),
        ]);
    }

    public function createHierarchicalChildWithEnumMapperCompiler(): MapperCompiler
    {
        return new ObjectInputMapperCompiler(HierarchicalWithEnumChildInput::class, [
            'id' => new IntInputMapperCompiler(),
            'type' => new EnumInputMapperCompiler(HierarchicalWithEnumType::class, new StringInputMapperCompiler()),
        ]);
    }

    private function createParentInputWithNoTypeFieldMapperCompiler(): MapperCompiler
    {
        return new DiscriminatedObjectInputMapperCompiler(
            HierarchicalWithNoTypeFieldParentInput::class,
            '$type',
            [
                'childOne' => new DelegateInputMapperCompiler(HierarchicalWithNoTypeFieldChildInput::class),
            ],
        );
    }

    public function createHierarchicalChildWithNoTypeFieldMapperCompiler(): MapperCompiler
    {
        return new ObjectInputMapperCompiler(HierarchicalWithNoTypeFieldChildInput::class, [
            'id' => new IntInputMapperCompiler(),
            'childOneField' => new StringInputMapperCompiler(),
        ], allowExtraKeys: true);
    }

}
