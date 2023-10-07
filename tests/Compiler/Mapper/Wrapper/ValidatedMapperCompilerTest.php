<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Wrapper;

use ShipMonk\InputMapper\Compiler\Exception\CannotCompileMapperException;
use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapInt;
use ShipMonk\InputMapper\Compiler\Mapper\Wrapper\ValidatedMapperCompiler;
use ShipMonk\InputMapper\Compiler\Validator\Int\AssertPositiveInt;
use ShipMonk\InputMapper\Compiler\Validator\String\AssertUrl;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonkTests\InputMapper\Compiler\Mapper\MapperCompilerTestCase;

class ValidatedMapperCompilerTest extends MapperCompilerTestCase
{

    public function testCompileWithEmptyValidatorList(): void
    {
        $mapperCompiler = new ValidatedMapperCompiler(new MapInt(), []);
        $mapper = $this->compileMapper('NotValidatedIntMapper', $mapperCompiler);
        self::assertSame(1, $mapper->map(1));
    }

    public function testCompile(): void
    {
        $mapperCompiler = new ValidatedMapperCompiler(new MapInt(), [new AssertPositiveInt()]);
        $mapper = $this->compileMapper('PositiveIntMapper', $mapperCompiler);

        self::assertSame(1, $mapper->map(1));

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected value greater than 0, got 0',
            static fn() => $mapper->map(0),
        );
    }

    public function testCompileWithIncompatibleValidator(): void
    {
        $mapperCompiler = new ValidatedMapperCompiler(new MapInt(), [new AssertUrl()]);

        self::assertException(
            CannotCompileMapperException::class,
            'Cannot compile mapper with validator ShipMonk\InputMapper\Compiler\Validator\String\AssertUrl, because mapper output type \'int\' is not compatible with validator input type \'string\'',
            fn() => $this->compileMapper('IntMapperWithIncompatibleValidator', $mapperCompiler),
        );
    }

}
