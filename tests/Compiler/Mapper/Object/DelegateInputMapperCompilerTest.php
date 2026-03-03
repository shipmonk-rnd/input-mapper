<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Object;

use ShipMonk\InputMapper\Compiler\Mapper\Input\DelegateInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Input\EnumInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Input\IntInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Input\ListInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Input\ObjectInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Input\OptionalInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Input\StringInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Type\GenericTypeParameter;
use ShipMonk\InputMapper\Runtime\Optional;
use ShipMonk\InputMapperTests\Compiler\Mapper\MapperCompilerTestCase;
use ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data\CollectionInput;
use ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data\PersonInput;
use ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data\SuitEnum;

class DelegateInputMapperCompilerTest extends MapperCompilerTestCase
{

    public function testCompile(): void
    {
        $delegateMapper = $this->compileMapper('DelegateToPerson', new DelegateInputMapperCompiler(PersonInput::class), [
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
        $collectionMapperCompiler = new ObjectInputMapperCompiler(
            className: CollectionInput::class,
            constructorArgsMapperCompilers: [
                'items' => new ListInputMapperCompiler(new DelegateInputMapperCompiler('T')),
                'size' => new IntInputMapperCompiler(),
            ],
            genericParameters: [
                new GenericTypeParameter('T'),
            ],
        );

        $intCollectionDelegateMapper = $this->compileMapper(
            name: 'DelegateToIntCollection',
            mapperCompiler: new DelegateInputMapperCompiler(CollectionInput::class, [
                new IntInputMapperCompiler(),
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
            mapperCompiler: new DelegateInputMapperCompiler(CollectionInput::class, [
                new DelegateInputMapperCompiler(SuitEnum::class),
            ]),
            providedMapperCompilers: [
                CollectionInput::class => $collectionMapperCompiler,
                SuitEnum::class => new EnumInputMapperCompiler(SuitEnum::class, new StringInputMapperCompiler()),
            ],
        );

        self::assertEquals(
            new CollectionInput([SuitEnum::Diamonds], 3),
            $enumCollectionDelegateMapper->map(['items' => [SuitEnum::Diamonds->value], 'size' => 3]),
        );
    }

    private function createPersonMapperCompiler(): MapperCompiler
    {
        return new ObjectInputMapperCompiler(PersonInput::class, [
            'id' => new IntInputMapperCompiler(),
            'name' => new StringInputMapperCompiler(),
            'age' => new OptionalInputMapperCompiler(new IntInputMapperCompiler()),
        ]);
    }

}
