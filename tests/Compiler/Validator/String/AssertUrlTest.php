<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Validator\String;

use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapString;
use ShipMonk\InputMapper\Compiler\Validator\String\AssertUrl;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonkTests\InputMapper\Compiler\Validator\ValidatorCompilerTestCase;

class AssertUrlTest extends ValidatorCompilerTestCase
{

    public function testUrlValidator(): void
    {
        $mapperCompiler = new MapString();
        $validatorCompiler = new AssertUrl();
        $validator = $this->compileValidator('UrlValidator', $mapperCompiler, $validatorCompiler);

        $validator->map('https://example.com');

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected valid URL, got "abc"',
            static fn() => $validator->map('abc'),
        );
    }

}
