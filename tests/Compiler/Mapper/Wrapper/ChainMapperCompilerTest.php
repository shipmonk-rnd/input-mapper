<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Wrapper;

use ShipMonk\InputMapper\Compiler\Exception\CannotCompileMapperException;
use ShipMonk\InputMapper\Compiler\Mapper\Input\ChainMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Input\IntInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Input\StringInputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapperTests\Compiler\Mapper\MapperCompilerTestCase;
use ShipMonk\InputMapperTests\Compiler\Mapper\Wrapper\Data\MapToDouble;

class ChainMapperCompilerTest extends MapperCompilerTestCase
{

    public function testCompile(): void
    {
        $mapperCompiler = new ChainMapperCompiler([new IntInputMapperCompiler(), new MapToDouble()]);
        $mapper = $this->compileInputMapper('DoubleInt', $mapperCompiler);

        self::assertSame(2, $mapper->map(1));
        self::assertSame(4, $mapper->map(2));
        self::assertSame(14, $mapper->map(7));

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected int, got "1"',
            static fn () => $mapper->map('1'),
        );
    }

    public function testCompileWithIncompatibleMapper(): void
    {
        $mapperCompiler = new ChainMapperCompiler([new StringInputMapperCompiler(), new MapToDouble()]);

        self::assertException(
            CannotCompileMapperException::class,
            'Cannot compile mapper ShipMonk\InputMapperTests\Compiler\Mapper\Wrapper\Data\MapToDouble, because its input type \'int\' is not super type of \'string\'',
            fn () => $this->compileInputMapper('DoubleIntIncompatible', $mapperCompiler),
        );
    }

}
