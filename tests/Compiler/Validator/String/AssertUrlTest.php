<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Validator\String;

use ShipMonk\InputMapper\Compiler\Exception\CannotCompileMapperException;
use ShipMonk\InputMapper\Compiler\Mapper\Input\IntInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Input\StringInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Validator\String\AssertUrl;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapperTests\Compiler\Validator\ValidatorCompilerTestCase;

class AssertUrlTest extends ValidatorCompilerTestCase
{

    public function testUrlValidatorWithIncompatibleMapperType(): void
    {
        $mapperCompiler = new IntInputMapperCompiler();
        $validatorCompiler = new AssertUrl();

        self::assertException(
            CannotCompileMapperException::class,
            'Cannot compile mapper with validator ShipMonk\InputMapper\Compiler\Validator\String\AssertUrl, because mapper output type \'int\' is not compatible with validator input type \'string\'',
            fn () => $this->compileValidator('UrlValidatorWithIncompatibleMapperType', $mapperCompiler, $validatorCompiler),
        );
    }

    public function testUrlValidator(): void
    {
        $mapperCompiler = new StringInputMapperCompiler();
        $validatorCompiler = new AssertUrl();
        $validator = $this->compileValidator('UrlValidator', $mapperCompiler, $validatorCompiler);

        $validator->map('https://example.com');

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected valid URL, got "abc"',
            static fn () => $validator->map('abc'),
        );
    }

}
