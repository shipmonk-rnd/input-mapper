<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Array;

use ShipMonk\InputMapper\Compiler\Mapper\Array\ArrayShapeItemMapping;
use ShipMonk\InputMapper\Compiler\Mapper\Array\MapArrayShape;
use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapInt;
use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapString;
use ShipMonk\InputMapper\Runtime\MappingFailedException;
use ShipMonkTests\InputMapper\Compiler\Mapper\MapperCompilerTestCase;

class MapArrayShapeTest extends MapperCompilerTestCase
{

    public function testCompileEmptySealedArrayShape(): void
    {
        $mapperCompiler = new MapArrayShape([], sealed: true);
        $mapper = $this->compileMapper($mapperCompiler);

        self::assertSame([], $mapper->map([]));

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: expected array, got null',
            static fn() => $mapper->map(null),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: expected array, got "1"',
            static fn() => $mapper->map('1'),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: expected array to not have keys [a, b], got {"a":1,"b":2}',
            static fn() => $mapper->map(['a' => 1, 'b' => 2]),
        );
    }

    public function testGetJsonSchemaForEmptySealedArrayShape(): void
    {
        $mapperCompiler = new MapArrayShape([], sealed: true);
        $jsonSchema = $mapperCompiler->getJsonSchema();

        self::assertSame(['type' => 'object', 'additionalProperties' => false], $jsonSchema);
    }

    public function testCompileEmptyUnsealedArrayShape(): void
    {
        $mapperCompiler = new MapArrayShape([], sealed: false);
        $mapper = $this->compileMapper($mapperCompiler);

        self::assertSame([], $mapper->map([]));
        self::assertSame([], $mapper->map(['a' => 1, 'b' => 2]));

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: expected array, got null',
            static fn() => $mapper->map(null),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: expected array, got "1"',
            static fn() => $mapper->map('1'),
        );
    }

    public function testGetJsonSchemaForEmptyUnsealedArrayShape(): void
    {
        $mapperCompiler = new MapArrayShape([], sealed: false);
        $jsonSchema = $mapperCompiler->getJsonSchema();

        self::assertSame(['type' => 'object'], $jsonSchema);
    }

    public function testCompileSealedArrayShape(): void
    {
        $items = [
            new ArrayShapeItemMapping('a', new MapInt()),
            new ArrayShapeItemMapping('b', new MapString(), optional: true),
        ];

        $mapperCompiler = new MapArrayShape($items, sealed: true);
        $mapper = $this->compileMapper($mapperCompiler);

        self::assertSame(['a' => 1, 'b' => '2'], $mapper->map(['a' => 1, 'b' => '2']));
        self::assertSame(['a' => 1, 'b' => '2'], $mapper->map(['b' => '2', 'a' => 1]));
        self::assertSame(['a' => 1], $mapper->map(['a' => 1]));

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: expected array, got null',
            static fn() => $mapper->map(null),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: expected array, got "1"',
            static fn() => $mapper->map('1'),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: expected key "a" to exist, got []',
            static fn() => $mapper->map([]),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /a: expected int, got null',
            static fn() => $mapper->map(['a' => null]),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /b: expected string, got 2',
            static fn() => $mapper->map(['a' => 1, 'b' => 2]),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: expected array to not have keys [c], got {"a":1,"c":2}',
            static fn() => $mapper->map(['a' => 1, 'c' => 2]),
        );
    }

    public function testGetJsonSchemaForSealedArrayShape(): void
    {
        $items = [
            new ArrayShapeItemMapping('a', new MapInt()),
            new ArrayShapeItemMapping('b', new MapString(), optional: true),
        ];

        $mapperCompiler = new MapArrayShape($items, sealed: true);
        $jsonSchema = $mapperCompiler->getJsonSchema();

        self::assertSame(
            [
                'type' => 'object',
                'properties' => [
                    'a' => ['type' => 'integer'],
                    'b' => ['type' => 'string'],
                ],
                'required' => ['a'],
                'additionalProperties' => false,
            ],
            $jsonSchema,
        );
    }

}
