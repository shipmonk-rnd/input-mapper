<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Object;

use ShipMonk\InputMapper\Compiler\Mapper\Array\MapList;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Object\DelegateMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Object\MapObject;
use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapInt;
use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapString;
use ShipMonk\InputMapper\Compiler\Mapper\Wrapper\MapOptional;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\MapperContext;
use ShipMonk\InputMapper\Runtime\Optional;
use ShipMonkTests\InputMapper\Compiler\Mapper\MapperCompilerTestCase;
use ShipMonkTests\InputMapper\Compiler\Mapper\Object\Data\MovieInput;
use ShipMonkTests\InputMapper\Compiler\Mapper\Object\Data\PersonInput;

class MapObjectTest extends MapperCompilerTestCase
{

    public function testCompile(): void
    {
        $personInputMapper = $this->compileMapper('Person', $this->createPersonInputMapperCompiler());
        $movieInputMapper = $this->compileMapper('Movie', $this->createMovieInputMapperCompiler(), [PersonInput::class => $personInputMapper]);

        $movieInputObject = new MovieInput(
            id: 1,
            title: 'The Matrix',
            description: Optional::of('A computer hacker learns from mysterious rebels about the true nature of his reality and his role in the war against its controllers.'),
            year: 1_999,
            genres: ['Action', 'Sci-Fi'],
            director: new PersonInput(7, 'Lana Wachowski', Optional::none(MapperContext::fromPath(['director']), 'age')),
            actors: [
                new PersonInput(8, 'Keanu Reeves', age: Optional::of(56)),
                new PersonInput(9, 'Laurence Fishburne', Optional::none(MapperContext::fromPath(['actors', 1]), 'age')),
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
            new PersonInput(1, 'John', Optional::none(null, 'age')),
            $mapper->map(['id' => 1, 'name' => 'John', 'extra' => 'X']),
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
