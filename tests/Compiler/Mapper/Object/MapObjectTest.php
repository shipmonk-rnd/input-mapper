<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Object;

use ShipMonk\InputMapper\Compiler\Mapper\Array\MapList;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Object\DelegateMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Object\MapObject;
use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapInt;
use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapString;
use ShipMonk\InputMapper\Compiler\Mapper\Wrapper\MapOptional;
use ShipMonk\InputMapper\Compiler\Type\GenericTypeParameter;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Optional;
use ShipMonkTests\InputMapper\Compiler\Mapper\MapperCompilerTestCase;
use ShipMonkTests\InputMapper\Compiler\Mapper\Object\Data\CollectionInput;
use ShipMonkTests\InputMapper\Compiler\Mapper\Object\Data\MovieInput;
use ShipMonkTests\InputMapper\Compiler\Mapper\Object\Data\PersonInput;

class MapObjectTest extends MapperCompilerTestCase
{

    public function testCompile(): void
    {
        $personInputMapperCompiler = $this->createPersonInputMapperCompiler();
        $movieInputMapper = $this->compileMapper('Movie', $this->createMovieInputMapperCompiler(), [PersonInput::class => $personInputMapperCompiler]);

        $movieInputObject = new MovieInput(
            id: 1,
            title: 'The Matrix',
            description: Optional::of('A computer hacker learns from mysterious rebels about the true nature of his reality and his role in the war against its controllers.'),
            year: 1_999,
            genres: ['Action', 'Sci-Fi'],
            director: new PersonInput(7, 'Lana Wachowski', Optional::none(['director'], 'age')),
            actors: [
                new PersonInput(8, 'Keanu Reeves', age: Optional::of(56)),
                new PersonInput(9, 'Laurence Fishburne', Optional::none(['actors', 1], 'age')),
            ],
        );

        $movieInputArray = [
            'id' => 1,
            'title' => 'The Matrix',
            'description' => 'A computer hacker learns from mysterious rebels about the true nature of his reality and his role in the war against its controllers.',
            'year' => 1_999,
            'genres' => ['Action', 'Sci-Fi'],
            'director' => [
                'id' => 7,
                'name' => 'Lana Wachowski',
            ],
            'actors' => [
                [
                    'id' => 8,
                    'name' => 'Keanu Reeves',
                    'age' => 56,
                ],
                [
                    'id' => 9,
                    'name' => 'Laurence Fishburne',
                ],
            ],
        ];

        self::assertEquals($movieInputObject, $movieInputMapper->map($movieInputArray));

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected array, got null',
            static fn() => $movieInputMapper->map(null),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected array, got 123',
            static fn() => $movieInputMapper->map(123),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Unrecognized key "extra"',
            static fn() => $movieInputMapper->map($movieInputArray + ['extra' => 1]),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /year: Expected int, got "X"',
            static fn() => $movieInputMapper->map(['year' => 'X'] + $movieInputArray),
        );
    }

    public function testCompileWithAllowExtraProperties(): void
    {
        $mapperCompiler = new MapObject(
            PersonInput::class,
            [
                'id' => new MapInt(),
                'name' => new MapString(),
                'age' => new MapOptional(new MapInt()),
            ],
            allowExtraKeys: true,
        );

        $mapper = $this->compileMapper('PersonWithAllowedExtraProperties', $mapperCompiler);

        self::assertEquals(
            new PersonInput(1, 'John', Optional::none([], 'age')),
            $mapper->map(['id' => 1, 'name' => 'John', 'extra' => 'X']),
        );
    }

    public function testCompileGeneric(): void
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

        $intCollectionMapper = $this->compileMapper('Collection', $collectionMapperCompiler, [], [
            $this->compileMapper('CollectionInnerInt', new MapInt()),
        ]);

        $stringCollectionMapper = $this->compileMapper('Collection', $collectionMapperCompiler, [], [
            $this->compileMapper('CollectionInnerString', new MapString()),
        ]);

        self::assertEquals(
            new CollectionInput([1, 2, 3], 3),
            $intCollectionMapper->map(['items' => [1, 2, 3], 'size' => 3]),
        );

        self::assertEquals(
            new CollectionInput(['a', 'b', 'c'], 3),
            $stringCollectionMapper->map(['items' => ['a', 'b', 'c'], 'size' => 3]),
        );
    }

    public function testCompileWithRenamedSourceKey(): void
    {
        $mapperCompiler = new MapObject(PersonInput::class, [
            'ID' => new MapInt(),
            'NAME' => new MapString(),
            'AGE' => new MapOptional(new MapInt()),
        ]);

        $mapper = $this->compileMapper('PersonWithRenamedSourceKeys', $mapperCompiler);

        self::assertEquals(
            new PersonInput(1, 'John', Optional::none([], 'AGE')),
            $mapper->map(['ID' => 1, 'NAME' => 'John']),
        );
    }

    private function createMovieInputMapperCompiler(): MapperCompiler
    {
        return new MapObject(MovieInput::class, [
            'id' => new MapInt(),
            'title' => new MapString(),
            'description' => new MapOptional(new MapString()),
            'year' => new MapInt(),
            'genres' => new MapList(new MapString()),
            'director' => new DelegateMapperCompiler(PersonInput::class),
            'actors' => new MapList(new DelegateMapperCompiler(PersonInput::class)),
        ]);
    }

    private function createPersonInputMapperCompiler(): MapperCompiler
    {
        return new MapObject(PersonInput::class, [
            'id' => new MapInt(),
            'name' => new MapString(),
            'age' => new MapOptional(new MapInt()),
        ]);
    }

}
