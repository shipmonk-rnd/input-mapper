<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Validator\String;

use ShipMonk\InputMapper\Compiler\Mapper\Input\StringInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Validator\String\AssertStringMatches;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapperTests\Compiler\Validator\ValidatorCompilerTestCase;

class AssertStringMatchesTest extends ValidatorCompilerTestCase
{

    public function testUrlValidator(): void
    {
        $mapperCompiler = new StringInputMapperCompiler();
        $validatorCompiler = new AssertStringMatches('#^\d+\z#');
        $validator = $this->compileValidator('PatternValidator', $mapperCompiler, $validatorCompiler);

        self::assertSame('123', $validator->map('123'));

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected string matching pattern #^\d+\z#, got "abc"',
            static fn () => $validator->map('abc'),
        );
    }

    public function testUrlValidatorWithCustomPatternDescription(): void
    {
        $mapperCompiler = new StringInputMapperCompiler();
        $validatorCompiler = new AssertStringMatches('#^\d+\z#', 'numeric string');
        $validator = $this->compileValidator('PatternValidatorWithCustomPatternDescription', $mapperCompiler, $validatorCompiler);

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected numeric string, got "abc"',
            static fn () => $validator->map('abc'),
        );
    }

}
