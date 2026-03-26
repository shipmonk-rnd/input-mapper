<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Object;

use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use ShipMonk\InputMapper\Compiler\Mapper\Output\DelegateOutputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Output\DiscriminatedObjectOutputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Output\ObjectOutputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Output\OptionalOutputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\PassthroughMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Optional;
use ShipMonk\InputMapperTests\Compiler\Mapper\MapperCompilerTestCase;
use ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data\HierarchicalChildOneInput;
use ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data\HierarchicalChildTwoInput;
use ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data\HierarchicalParentInput;
use ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data\SimplePersonInput;

class DiscriminatedObjectOutputMapperCompilerTest extends MapperCompilerTestCase
{

    public function testCompile(): void
    {
        $mapperCompiler = new DiscriminatedObjectOutputMapperCompiler(
            HierarchicalParentInput::class,
            [
                'childOne' => new DelegateOutputMapperCompiler(HierarchicalChildOneInput::class),
                'childTwo' => new DelegateOutputMapperCompiler(HierarchicalChildTwoInput::class),
            ],
        );

        $childOneOutputMapperCompiler = new ObjectOutputMapperCompiler(HierarchicalChildOneInput::class, [
            'id' => ['id', new PassthroughMapperCompiler(new IdentifierTypeNode('int'))],
            'name' => ['name', new PassthroughMapperCompiler(new IdentifierTypeNode('string'))],
            'age' => ['age', new OptionalOutputMapperCompiler(new PassthroughMapperCompiler(new IdentifierTypeNode('int')))],
            'type' => ['type', new PassthroughMapperCompiler(new IdentifierTypeNode('string'))],
            'childOneField' => ['childOneField', new PassthroughMapperCompiler(new IdentifierTypeNode('string'))],
        ]);

        $childTwoOutputMapperCompiler = new ObjectOutputMapperCompiler(HierarchicalChildTwoInput::class, [
            'id' => ['id', new PassthroughMapperCompiler(new IdentifierTypeNode('int'))],
            'name' => ['name', new PassthroughMapperCompiler(new IdentifierTypeNode('string'))],
            'age' => ['age', new OptionalOutputMapperCompiler(new PassthroughMapperCompiler(new IdentifierTypeNode('int')))],
            'type' => ['type', new PassthroughMapperCompiler(new IdentifierTypeNode('string'))],
            'childTwoField' => ['childTwoField', new PassthroughMapperCompiler(new IdentifierTypeNode('int'))],
        ]);

        $mapper = $this->compileOutputMapper('HierarchicalParent', $mapperCompiler, [
            HierarchicalChildOneInput::class => $childOneOutputMapperCompiler,
            HierarchicalChildTwoInput::class => $childTwoOutputMapperCompiler,
        ]);

        $childOne = new HierarchicalChildOneInput(
            id: 1,
            name: 'Alice',
            age: Optional::of(30),
            type: 'childOne',
            childOneField: 'extra',
        );

        self::assertSame(
            ['id' => 1, 'name' => 'Alice', 'age' => 30, 'type' => 'childOne', 'childOneField' => 'extra'],
            $mapper->map($childOne),
        );

        $childTwo = new HierarchicalChildTwoInput(
            id: 2,
            name: 'Bob',
            age: Optional::none([], 'age'),
            type: 'childTwo',
            childTwoField: 42,
        );

        self::assertSame(
            ['id' => 2, 'name' => 'Bob', 'type' => 'childTwo', 'childTwoField' => 42],
            $mapper->map($childTwo),
        );
    }

    public function testCompileWithUnrecognizedSubtype(): void
    {
        $mapperCompiler = new DiscriminatedObjectOutputMapperCompiler(
            HierarchicalParentInput::class,
            [
                'childOne' => new DelegateOutputMapperCompiler(HierarchicalChildOneInput::class),
                'childTwo' => new DelegateOutputMapperCompiler(HierarchicalChildTwoInput::class),
            ],
        );

        $childOneOutputMapperCompiler = new ObjectOutputMapperCompiler(HierarchicalChildOneInput::class, [
            'id' => ['id', new PassthroughMapperCompiler(new IdentifierTypeNode('int'))],
            'name' => ['name', new PassthroughMapperCompiler(new IdentifierTypeNode('string'))],
            'age' => ['age', new OptionalOutputMapperCompiler(new PassthroughMapperCompiler(new IdentifierTypeNode('int')))],
            'type' => ['type', new PassthroughMapperCompiler(new IdentifierTypeNode('string'))],
            'childOneField' => ['childOneField', new PassthroughMapperCompiler(new IdentifierTypeNode('string'))],
        ]);

        $childTwoOutputMapperCompiler = new ObjectOutputMapperCompiler(HierarchicalChildTwoInput::class, [
            'id' => ['id', new PassthroughMapperCompiler(new IdentifierTypeNode('int'))],
            'name' => ['name', new PassthroughMapperCompiler(new IdentifierTypeNode('string'))],
            'age' => ['age', new OptionalOutputMapperCompiler(new PassthroughMapperCompiler(new IdentifierTypeNode('int')))],
            'type' => ['type', new PassthroughMapperCompiler(new IdentifierTypeNode('string'))],
            'childTwoField' => ['childTwoField', new PassthroughMapperCompiler(new IdentifierTypeNode('int'))],
        ]);

        $mapper = $this->compileOutputMapper('HierarchicalParent', $mapperCompiler, [
            HierarchicalChildOneInput::class => $childOneOutputMapperCompiler,
            HierarchicalChildTwoInput::class => $childTwoOutputMapperCompiler,
        ]);

        $unrecognized = new SimplePersonInput(id: 1, name: 'Alice');

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected %s, got %s',
            static fn () => $mapper->map($unrecognized),
        );
    }

}
