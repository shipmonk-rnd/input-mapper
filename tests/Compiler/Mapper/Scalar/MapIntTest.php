<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Scalar;

use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapInt;
use ShipMonk\InputMapper\Runtime\MappingFailedException;
use ShipMonkTests\InputMapper\Compiler\Mapper\MapperCompilerTestCase;

class MapIntTest extends MapperCompilerTestCase
{

    public function testCompile(): void
    {
        $mapperCompiler = new MapInt();
        $mapper = $this->compileMapper('Int', $mapperCompiler);

        self::assertSame(1, $mapper->map(1));
        self::assertSame(2, $mapper->map(2));

        self::assertException(MappingFailedException::class, null, static fn() => $mapper->map('1'));
        self::assertException(MappingFailedException::class, null, static fn() => $mapper->map(null));
        self::assertException(MappingFailedException::class, null, static fn() => $mapper->map([]));
    }

    public function testGetJsonSchema(): void
    {
        $mapperCompiler = new MapInt();
        self::assertSame(['type' => 'integer'], $mapperCompiler->getJsonSchema());
    }

}
