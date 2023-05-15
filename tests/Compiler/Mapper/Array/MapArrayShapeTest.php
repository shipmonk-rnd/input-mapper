<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Array;

use ShipMonk\InputMapper\Compiler\Mapper\Array\ArrayShapeItemMapping;
use ShipMonk\InputMapper\Compiler\Mapper\Array\MapArrayShape;
use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapInt;
use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapString;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonkTests\InputMapper\Compiler\Mapper\MapperCompilerTestCase;

class MapArrayShapeTest extends MapperCompilerTestCase
{

    public function testCompileEmptySealedArrayShape(): void
    {
        $mapperCompiler = new MapArrayShape([], sealed: true);
        $mapper = $this->compileMapper('EmptySealedArrayShape', $mapperCompiler);

        self::assertSame([], $mapper->map([]));

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected array, got null',
            static fn() => $mapper->map(null),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected array, got "1"',
            static fn() => $mapper->map('1'),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Unrecognized keys "a" and "b"',
            static fn() => $mapper->map(['a' => 1, 'b' => 2]),
        );
    }

    public function testCompileEmptyUnsealedArrayShape(): void
    {
        $mapperCompiler = new MapArrayShape([], sealed: false);
        $mapper = $this->compileMapper('EmptyUnsealedArrayShape', $mapperCompiler);

        self::assertSame([], $mapper->map([]));
        self::assertSame([], $mapper->map(['a' => 1, 'b' => 2]));

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected array, got null',
            static fn() => $mapper->map(null),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected array, got "1"',
            static fn() => $mapper->map('1'),
        );
    }

    public function testCompileSealedArrayShape(): void
    {
        $items = [
            new ArrayShapeItemMapping('a', new MapInt()),
            new ArrayShapeItemMapping('b', new MapString(), optional: true),
        ];

        $mapperCompiler = new MapArrayShape($items, sealed: true);
        $mapper = $this->compileMapper('SealedArrayShape', $mapperCompiler);

        self::assertSame(['a' => 1, 'b' => '2'], $mapper->map(['a' => 1, 'b' => '2']));
        self::assertSame(['a' => 1, 'b' => '2'], $mapper->map(['b' => '2', 'a' => 1]));
        self::assertSame(['a' => 1], $mapper->map(['a' => 1]));

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected array, got null',
            static fn() => $mapper->map(null),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected array, got "1"',
            static fn() => $mapper->map('1'),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Missing required key "a"',
            static fn() => $mapper->map([]),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /a: Expected int, got null',
            static fn() => $mapper->map(['a' => null]),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /b: Expected string, got 2',
            static fn() => $mapper->map(['a' => 1, 'b' => 2]),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Unrecognized key "c"',
            static fn() => $mapper->map(['a' => 1, 'c' => 2]),
        );
    }

}
