<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Object;

use ShipMonk\InputMapper\Compiler\Mapper\Input\IntInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Input\ListInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Input\ObjectInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Input\OptionalInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Input\StringInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Input\DelegateInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Type\GenericTypeParameter;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Optional;
use ShipMonk\InputMapperTests\Compiler\Mapper\MapperCompilerTestCase;
use ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data\CollectionInput;
use ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data\MovieInput;
use ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data\PersonInput;

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
            static fn () => $movieInputMapper->map(null),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected array, got 123',
            static fn () => $movieInputMapper->map(123),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Unrecognized key "extra"',
            static fn () => $movieInputMapper->map($movieInputArray + ['extra' => 1]),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /year: Expected int, got "X"',
            static fn () => $movieInputMapper->map(['year' => 'X'] + $movieInputArray),
        );
    }

    public function testCompileWithAllowExtraProperties(): void
    {
        $mapperCompiler = new ObjectInputMapperCompiler(
            PersonInput::class,
            [
                'id' => new IntInputMapperCompiler(),
                'name' => new StringInputMapperCompiler(),
                'age' => new OptionalInputMapperCompiler(new IntInputMapperCompiler()),
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

        $intCollectionMapper = $this->compileMapper('Collection', $collectionMapperCompiler, [], [
            $this->compileMapper('CollectionInnerInt', new IntInputMapperCompiler()),
        ]);

        $stringCollectionMapper = $this->compileMapper('Collection', $collectionMapperCompiler, [], [
            $this->compileMapper('CollectionInnerString', new StringInputMapperCompiler()),
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
        $mapperCompiler = new ObjectInputMapperCompiler(PersonInput::class, [
            'ID' => new IntInputMapperCompiler(),
            'NAME' => new StringInputMapperCompiler(),
            'AGE' => new OptionalInputMapperCompiler(new IntInputMapperCompiler()),
        ]);

        $mapper = $this->compileMapper('PersonWithRenamedSourceKeys', $mapperCompiler);

        self::assertEquals(
            new PersonInput(1, 'John', Optional::none([], 'AGE')),
            $mapper->map(['ID' => 1, 'NAME' => 'John']),
        );
    }

    private function createMovieInputMapperCompiler(): MapperCompiler
    {
        return new ObjectInputMapperCompiler(MovieInput::class, [
            'id' => new IntInputMapperCompiler(),
            'title' => new StringInputMapperCompiler(),
            'description' => new OptionalInputMapperCompiler(new StringInputMapperCompiler()),
            'year' => new IntInputMapperCompiler(),
            'genres' => new ListInputMapperCompiler(new StringInputMapperCompiler()),
            'director' => new DelegateInputMapperCompiler(PersonInput::class),
            'actors' => new ListInputMapperCompiler(new DelegateInputMapperCompiler(PersonInput::class)),
        ]);
    }

    private function createPersonInputMapperCompiler(): MapperCompiler
    {
        return new ObjectInputMapperCompiler(PersonInput::class, [
            'id' => new IntInputMapperCompiler(),
            'name' => new StringInputMapperCompiler(),
            'age' => new OptionalInputMapperCompiler(new IntInputMapperCompiler()),
        ]);
    }

}
