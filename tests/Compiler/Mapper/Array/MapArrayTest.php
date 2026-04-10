<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Array;

use ShipMonk\InputMapper\Compiler\Mapper\Input\ArrayInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Input\IntInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Input\StringInputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapperTests\Compiler\Mapper\MapperCompilerTestCase;

class MapArrayTest extends MapperCompilerTestCase
{

    public function testCompile(): void
    {
        $keyMapperCompiler = new StringInputMapperCompiler();
        $valueMapperCompiler = new IntInputMapperCompiler();
        $mapperCompiler = new ArrayInputMapperCompiler($keyMapperCompiler, $valueMapperCompiler);
        $mapper = $this->compileInputMapper('GenericArray', $mapperCompiler);

        self::assertSame([], $mapper->map([]));
        self::assertSame(['a' => 1], $mapper->map(['a' => 1]));
        self::assertSame(['a' => 1, 'b' => 2], $mapper->map(['a' => 1, 'b' => 2]));

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected array, got "1"',
            static fn () => $mapper->map('1'),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected array, got null',
            static fn () => $mapper->map(null),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /a: Expected int, got "1"',
            static fn () => $mapper->map(['a' => '1']),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /0: Expected string, got 0',
            static fn () => $mapper->map([1]),
        );
    }

}
