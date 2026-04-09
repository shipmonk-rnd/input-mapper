<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Validator\String;

use ShipMonk\InputMapper\Compiler\Mapper\Input\StringInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Validator\String\AssertStringNonEmpty;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapperTests\Compiler\Validator\ValidatorCompilerTestCase;

class AssertStringNonEmptyTest extends ValidatorCompilerTestCase
{

    public function testNonEmptyStringValidator(): void
    {
        $mapperCompiler = new StringInputMapperCompiler();
        $validatorCompiler = new AssertStringNonEmpty();
        $validator = $this->compileValidator('StringNonEmptyValidator', $mapperCompiler, $validatorCompiler);

        self::assertSame('hello', $validator->map('hello'));
        self::assertSame('a', $validator->map('a'));
        self::assertSame(' a ', $validator->map(' a '));

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected non-empty string, got ""',
            static fn () => $validator->map(''),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected non-empty string, got " "',
            static fn () => $validator->map(' '),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected non-empty string, got "  "',
            static fn () => $validator->map('  '),
        );
    }

}
