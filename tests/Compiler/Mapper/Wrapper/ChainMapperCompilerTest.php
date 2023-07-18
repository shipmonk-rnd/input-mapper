<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Wrapper;

use ShipMonk\InputMapper\Compiler\Exception\CannotCompileMapperException;
use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapInt;
use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapString;
use ShipMonk\InputMapper\Compiler\Mapper\Wrapper\ChainMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonkTests\InputMapper\Compiler\Mapper\MapperCompilerTestCase;
use ShipMonkTests\InputMapper\Compiler\Mapper\Wrapper\Data\MapToDouble;

class ChainMapperCompilerTest extends MapperCompilerTestCase
{

    public function testCompile(): void
    {
        $mapperCompiler = new ChainMapperCompiler([new MapInt(), new MapToDouble()]);
        $mapper = $this->compileMapper('DoubleInt', $mapperCompiler);

        self::assertSame(2, $mapper->map(1));
        self::assertSame(4, $mapper->map(2));
        self::assertSame(14, $mapper->map(7));

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected int, got "1"',
            static fn() => $mapper->map('1'),
        );
    }

    public function testCompileWithIncompatibleMapper(): void
    {
        $mapperCompiler = new ChainMapperCompiler([new MapString(), new MapToDouble()]);

        self::assertException(
            CannotCompileMapperException::class,
            'Cannot compile mapper ShipMonkTests\InputMapper\Compiler\Mapper\Wrapper\Data\MapToDouble, because its input type \'int\' is not super type of \'string\'',
            fn() => $this->compileMapper('DoubleIntIncompatible', $mapperCompiler),
        );
    }

}
