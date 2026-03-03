<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Mixed;

use DateTimeImmutable;
use ShipMonk\InputMapper\Compiler\Attribute\MapMixed;
use ShipMonk\InputMapperTests\Compiler\Mapper\MapperCompilerTestCase;

class MapMixedTest extends MapperCompilerTestCase
{

    public function testCompile(): void
    {
        $mapperCompiler = new MapMixed();
        $mapper = $this->compileMapper('Mixed', $mapperCompiler);

        self::assertNull($mapper->map(null));
        self::assertSame(1, $mapper->map(1));
        self::assertSame('A', $mapper->map('A'));
        self::assertSame([], $mapper->map([]));
        self::assertSame([1], $mapper->map([1]));

        $object = new DateTimeImmutable();
        self::assertSame($object, $mapper->map($object));
    }

}
