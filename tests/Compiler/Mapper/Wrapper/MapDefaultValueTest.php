<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Wrapper;

use ShipMonk\InputMapper\Compiler\Mapper\Input\DefaultValueInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Input\EnumInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Input\IntInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Input\NullableInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Input\ObjectInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Input\StringInputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapperTests\Compiler\Mapper\MapperCompilerTestCase;
use ShipMonk\InputMapperTests\Compiler\Mapper\Wrapper\Data\Semaphore;
use ShipMonk\InputMapperTests\Compiler\Mapper\Wrapper\Data\SemaphoreColorEnum;

class MapDefaultValueTest extends MapperCompilerTestCase
{

    public function testCompile(): void
    {
        $mapperCompiler = new DefaultValueInputMapperCompiler(new IntInputMapperCompiler(), null);
        $mapper = $this->compileMapper('IntWithDefaultValue', $mapperCompiler);

        self::assertSame(1, $mapper->map(1));
        self::assertSame(2, $mapper->map(2));

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected int, got "1"',
            static fn () => $mapper->map('1'),
        );
    }

    public function testCompileUndefined(): void
    {
        $mapperCompiler = new ObjectInputMapperCompiler(Semaphore::class, [
            'color' => new DefaultValueInputMapperCompiler(new EnumInputMapperCompiler(SemaphoreColorEnum::class, new StringInputMapperCompiler()), SemaphoreColorEnum::Green),
            'manufacturer' => new DefaultValueInputMapperCompiler(new NullableInputMapperCompiler(new StringInputMapperCompiler()), null),
        ]);

        $mapper = $this->compileMapper('Semaphore', $mapperCompiler);

        self::assertEquals(new Semaphore(SemaphoreColorEnum::Green, null), $mapper->map([]));
        self::assertEquals(new Semaphore(SemaphoreColorEnum::Red, null), $mapper->map(['color' => 'red']));
        self::assertEquals(new Semaphore(SemaphoreColorEnum::Red, 'Siemens'), $mapper->map(['color' => 'red', 'manufacturer' => 'Siemens']));
    }

}
