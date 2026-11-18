<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Wrapper;

use ShipMonk\InputMapper\Compiler\Mapper\Input\ListInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Input\SensitiveInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Input\StringInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Input\ValidatedInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Validator\String\AssertStringNonEmpty;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapperTests\Compiler\Mapper\MapperCompilerTestCase;

class SensitiveInputMapperCompilerTest extends MapperCompilerTestCase
{

    public function testCompile(): void
    {
        $mapperCompiler = new SensitiveInputMapperCompiler(new StringInputMapperCompiler());
        $mapper = $this->compileInputMapper('SensitiveString', $mapperCompiler);

        self::assertSame('secret', $mapper->map('secret'));

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected string, got int (redacted)',
            static fn () => $mapper->map(12_345),
        );
    }

    public function testCompileWithValidator(): void
    {
        $mapperCompiler = new SensitiveInputMapperCompiler(
            new ValidatedInputMapperCompiler(new StringInputMapperCompiler(), [new AssertStringNonEmpty()]),
        );

        $mapper = $this->compileInputMapper('SensitiveNonEmptyString', $mapperCompiler);

        self::assertSame('secret', $mapper->map('secret'));

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected non-empty string, got string (redacted)',
            static fn () => $mapper->map(' '),
        );
    }

    public function testCompileWithNestedMapper(): void
    {
        $mapperCompiler = new SensitiveInputMapperCompiler(new ListInputMapperCompiler(new StringInputMapperCompiler()));
        $mapper = $this->compileInputMapper('SensitiveStringList', $mapperCompiler);

        self::assertSame(['secret'], $mapper->map(['secret']));

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /0: Expected string, got int (redacted)',
            static fn () => $mapper->map([12_345]),
        );
    }

}
