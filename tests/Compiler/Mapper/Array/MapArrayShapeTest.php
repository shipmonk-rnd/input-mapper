<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Array;

use ShipMonk\InputMapper\Compiler\Mapper\Input\ArrayShapeInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Input\IntInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Input\StringInputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapperTests\Compiler\Mapper\MapperCompilerTestCase;

class MapArrayShapeTest extends MapperCompilerTestCase
{

    public function testCompileEmptySealedArrayShape(): void
    {
        $mapperCompiler = new ArrayShapeInputMapperCompiler([], sealed: true);
        $mapper = $this->compileInputMapper('EmptySealedArrayShape', $mapperCompiler);

        self::assertSame([], $mapper->map([]));

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected array, got null',
            static fn () => $mapper->map(null),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected array, got "1"',
            static fn () => $mapper->map('1'),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Unrecognized keys "a" and "b"',
            static fn () => $mapper->map(['a' => 1, 'b' => 2]),
        );
    }

    public function testCompileEmptyUnsealedArrayShape(): void
    {
        $mapperCompiler = new ArrayShapeInputMapperCompiler([], sealed: false);
        $mapper = $this->compileInputMapper('EmptyUnsealedArrayShape', $mapperCompiler);

        self::assertSame([], $mapper->map([]));
        self::assertSame([], $mapper->map(['a' => 1, 'b' => 2]));

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected array, got null',
            static fn () => $mapper->map(null),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected array, got "1"',
            static fn () => $mapper->map('1'),
        );
    }

    public function testCompileSealedArrayShape(): void
    {
        $items = [
            ['key' => 'a', 'mapper' => new IntInputMapperCompiler(), 'optional' => false],
            ['key' => 'b', 'mapper' => new StringInputMapperCompiler(), 'optional' => true],
        ];

        $mapperCompiler = new ArrayShapeInputMapperCompiler($items, sealed: true);
        $mapper = $this->compileInputMapper('SealedArrayShape', $mapperCompiler);

        self::assertSame(['a' => 1, 'b' => '2'], $mapper->map(['a' => 1, 'b' => '2']));
        self::assertSame(['a' => 1, 'b' => '2'], $mapper->map(['b' => '2', 'a' => 1]));
        self::assertSame(['a' => 1], $mapper->map(['a' => 1]));

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected array, got null',
            static fn () => $mapper->map(null),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected array, got "1"',
            static fn () => $mapper->map('1'),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Missing required key "a"',
            static fn () => $mapper->map([]),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /a: Expected int, got null',
            static fn () => $mapper->map(['a' => null]),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /b: Expected string, got 2',
            static fn () => $mapper->map(['a' => 1, 'b' => 2]),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Unrecognized key "c"',
            static fn () => $mapper->map(['a' => 1, 'c' => 2]),
        );
    }

    public function testCompileWithKeysWhichAreNotValidIdentifiers(): void
    {
        $items = [
            ['key' => 'bom-ref', 'mapper' => new IntInputMapperCompiler(), 'optional' => false],
            ['key' => 'x:y', 'mapper' => new StringInputMapperCompiler(), 'optional' => true],
        ];

        $mapperCompiler = new ArrayShapeInputMapperCompiler($items, sealed: true);
        $mapper = $this->compileInputMapper('ArrayShapeWithInvalidIdentifierKeys', $mapperCompiler);

        self::assertSame(['bom-ref' => 1, 'x:y' => '2'], $mapper->map(['bom-ref' => 1, 'x:y' => '2']));
        self::assertSame(['bom-ref' => 1], $mapper->map(['bom-ref' => 1]));

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /x:y: Expected string, got 2',
            static fn () => $mapper->map(['bom-ref' => 1, 'x:y' => 2]),
        );
    }

}
