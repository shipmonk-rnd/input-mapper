<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Object;

use ShipMonk\InputMapper\Compiler\Mapper\Array\MapList;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Object\DelegateMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Object\MapEnum;
use ShipMonk\InputMapper\Compiler\Mapper\Object\MapObject;
use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapInt;
use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapString;
use ShipMonk\InputMapper\Compiler\Mapper\Wrapper\MapOptional;
use ShipMonk\InputMapper\Compiler\Type\GenericTypeParameter;
use ShipMonk\InputMapper\Runtime\Optional;
use ShipMonkTests\InputMapper\Compiler\Mapper\MapperCompilerTestCase;
use ShipMonkTests\InputMapper\Compiler\Mapper\Object\Data\CollectionInput;
use ShipMonkTests\InputMapper\Compiler\Mapper\Object\Data\PersonInput;
use ShipMonkTests\InputMapper\Compiler\Mapper\Object\Data\SuitEnum;

class DelegateMapperCompilerTest extends MapperCompilerTestCase
{

    public function testCompile(): void
    {
        $delegateMapper = $this->compileMapper('DelegateToPerson', new DelegateMapperCompiler(PersonInput::class), [
            PersonInput::class => $this->createPersonMapperCompiler(),
        ]);

        $personInputArray = [
            'id' => 7,
            'name' => 'Lana Wachowski',
        ];

        $personObject = new PersonInput(
            id: 7,
            name: 'Lana Wachowski',
            age: Optional::none([], 'age'),
        );

        self::assertEquals($personObject, $delegateMapper->map($personInputArray));
    }

    public function testCompileWithInnerMapper(): void
    {
        $collectionMapperCompiler = new MapObject(
            className: CollectionInput::class,
            constructorArgsMapperCompilers: [
                'items' => new MapList(new DelegateMapperCompiler('T')),
                'size' => new MapInt(),
            ],
            genericParameters: [
                new GenericTypeParameter('T'),
            ],
        );

        $intCollectionDelegateMapper = $this->compileMapper(
            name: 'DelegateToIntCollection',
            mapperCompiler: new DelegateMapperCompiler(CollectionInput::class, [
                new MapInt(),
            ]),
            providedMapperCompilers: [
                CollectionInput::class => $collectionMapperCompiler,
            ],
        );

        self::assertEquals(
            new CollectionInput([1, 2, 3], 3),
            $intCollectionDelegateMapper->map(['items' => [1, 2, 3], 'size' => 3]),
        );

        $enumCollectionDelegateMapper = $this->compileMapper(
            name: 'DelegateToEnumCollection',
            mapperCompiler: new DelegateMapperCompiler(CollectionInput::class, [
                new DelegateMapperCompiler(SuitEnum::class),
            ]),
            providedMapperCompilers: [
                CollectionInput::class => $collectionMapperCompiler,
                SuitEnum::class => new MapEnum(SuitEnum::class, new MapString()),
            ],
        );

        self::assertEquals(
            new CollectionInput([SuitEnum::Diamonds], 3),
            $enumCollectionDelegateMapper->map(['items' => [SuitEnum::Diamonds->value], 'size' => 3]),
        );
    }

    private function createPersonMapperCompiler(): MapperCompiler
    {
        return new MapObject(PersonInput::class, [
            'id' => new MapInt(),
            'name' => new MapString(),
            'age' => new MapOptional(new MapInt()),
        ]);
    }

}
