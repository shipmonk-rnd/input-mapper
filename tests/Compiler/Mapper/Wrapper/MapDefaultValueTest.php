<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Wrapper;

use ShipMonk\InputMapper\Compiler\Mapper\Object\MapEnum;
use ShipMonk\InputMapper\Compiler\Mapper\Object\MapObject;
use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapInt;
use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapString;
use ShipMonk\InputMapper\Compiler\Mapper\Wrapper\MapDefaultValue;
use ShipMonk\InputMapper\Compiler\Mapper\Wrapper\MapNullable;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonkTests\InputMapper\Compiler\Mapper\MapperCompilerTestCase;
use ShipMonkTests\InputMapper\Compiler\Mapper\Wrapper\Data\Semaphore;
use ShipMonkTests\InputMapper\Compiler\Mapper\Wrapper\Data\SemaphoreColorEnum;

class MapDefaultValueTest extends MapperCompilerTestCase
{

    public function testCompile(): void
    {
        $mapperCompiler = new MapDefaultValue(new MapInt(), null);
        $mapper = $this->compileMapper('IntWithDefaultValue', $mapperCompiler);

        self::assertSame(1, $mapper->map(1));
        self::assertSame(2, $mapper->map(2));

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected int, got "1"',
            static fn() => $mapper->map('1'),
        );
    }

    public function testCompileUndefined(): void
    {
        $mapperCompiler = new MapObject(Semaphore::class, [
            'color' => new MapDefaultValue(new MapEnum(SemaphoreColorEnum::class, new MapString()), SemaphoreColorEnum::Green),
            'manufacturer' => new MapDefaultValue(new MapNullable(new MapString()), null),
        ]);

        $mapper = $this->compileMapper('Semaphore', $mapperCompiler);

        self::assertEquals(new Semaphore(SemaphoreColorEnum::Green, null), $mapper->map([]));
        self::assertEquals(new Semaphore(SemaphoreColorEnum::Red, null), $mapper->map(['color' => 'red']));
        self::assertEquals(new Semaphore(SemaphoreColorEnum::Red, 'Siemens'), $mapper->map(['color' => 'red', 'manufacturer' => 'Siemens']));
    }

}
