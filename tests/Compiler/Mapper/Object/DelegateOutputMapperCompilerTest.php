<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Object;

use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Output\DelegateOutputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Output\EnumOutputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Output\ObjectOutputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Output\OptionalOutputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\PassthroughMapperCompiler;
use ShipMonk\InputMapper\Compiler\Type\GenericTypeParameter;
use ShipMonk\InputMapper\Runtime\Optional;
use ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data\CollectionInput;
use ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data\PersonInput;
use ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data\SuitEnum;
use ShipMonk\InputMapperTests\Compiler\Mapper\OutputMapperCompilerTestCase;

class DelegateOutputMapperCompilerTest extends OutputMapperCompilerTestCase
{

    public function testCompile(): void
    {
        $delegateMapper = $this->compileOutputMapper('DelegateToPerson', new DelegateOutputMapperCompiler(PersonInput::class), [
            PersonInput::class => $this->createPersonOutputMapperCompiler(),
        ]);

        $personObject = new PersonInput(
            id: 7,
            name: 'Lana Wachowski',
            age: Optional::none([], 'age'),
        );

        self::assertSame(
            ['id' => 7, 'name' => 'Lana Wachowski'],
            $delegateMapper->map($personObject),
        );
    }

    public function testCompileWithInnerMapper(): void
    {
        $collectionOutputMapperCompiler = new ObjectOutputMapperCompiler(
            className: CollectionInput::class,
            propertyMapperCompilers: [
                'items' => ['items', new PassthroughMapperCompiler(new IdentifierTypeNode('mixed'))],
                'size' => ['size', new PassthroughMapperCompiler(new IdentifierTypeNode('int'))],
            ],
            genericParameters: [
                new GenericTypeParameter('T'),
            ],
        );

        $intCollectionDelegateMapper = $this->compileOutputMapper(
            name: 'DelegateToIntCollection',
            mapperCompiler: new DelegateOutputMapperCompiler(CollectionInput::class, [
                new PassthroughMapperCompiler(new IdentifierTypeNode('int')),
            ]),
            providedMapperCompilers: [
                CollectionInput::class => $collectionOutputMapperCompiler,
            ],
        );

        self::assertSame(
            ['items' => [1, 2, 3], 'size' => 3],
            $intCollectionDelegateMapper->map(new CollectionInput([1, 2, 3], 3)),
        );

        $enumCollectionDelegateMapper = $this->compileOutputMapper(
            name: 'DelegateToEnumCollection',
            mapperCompiler: new DelegateOutputMapperCompiler(CollectionInput::class, [
                new DelegateOutputMapperCompiler(SuitEnum::class),
            ]),
            providedMapperCompilers: [
                CollectionInput::class => $collectionOutputMapperCompiler,
                SuitEnum::class => new EnumOutputMapperCompiler(SuitEnum::class),
            ],
        );

        self::assertSame(
            ['items' => [SuitEnum::Diamonds], 'size' => 3],
            $enumCollectionDelegateMapper->map(new CollectionInput([SuitEnum::Diamonds], 3)),
        );
    }

    private function createPersonOutputMapperCompiler(): MapperCompiler
    {
        return new ObjectOutputMapperCompiler(PersonInput::class, [
            'id' => ['id', new PassthroughMapperCompiler(new IdentifierTypeNode('int'))],
            'name' => ['name', new PassthroughMapperCompiler(new IdentifierTypeNode('string'))],
            'age' => ['age', new OptionalOutputMapperCompiler(new PassthroughMapperCompiler(new IdentifierTypeNode('int')))],
        ]);
    }

}
