<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Mixed;

use DateTimeImmutable;
use ShipMonk\InputMapper\Compiler\Mapper\Mixed\MapMixed;
use ShipMonkTests\InputMapper\Compiler\Mapper\MapperCompilerTestCase;

class MapMixedTest extends MapperCompilerTestCase
{

    public function testCompile(): void
    {
        $mapperCompiler = new MapMixed();
        $mapper = $this->compileMapper('Mixed', $mapperCompiler);

        self::assertSame(null, $mapper->map(null));
        self::assertSame(1, $mapper->map(1));
        self::assertSame('A', $mapper->map('A'));
        self::assertSame([], $mapper->map([]));
        self::assertSame([1], $mapper->map([1]));

        $object = new DateTimeImmutable();
        self::assertSame($object, $mapper->map($object));
    }

    public function testGetJsonSchema(): void
    {
        $mapperCompiler = new MapMixed();
        self::assertSame([], $mapperCompiler->getJsonSchema());
    }

}
