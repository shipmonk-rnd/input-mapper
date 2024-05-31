<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Validator\Array;

use ShipMonk\InputMapper\Compiler\Mapper\Array\MapList;
use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapInt;
use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapString;
use ShipMonk\InputMapper\Compiler\Validator\Array\AssertUniqueItems;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonkTests\InputMapper\Compiler\Validator\ValidatorCompilerTestCase;

class AssertUniqueItemsTest extends ValidatorCompilerTestCase
{

    public function testUniqueItemsIntValidator(): void
    {
        $mapperCompiler = new MapList(new MapInt());
        $validatorCompiler = new AssertUniqueItems();
        $validator = $this->compileValidator('UniqueItemsIntValidator', $mapperCompiler, $validatorCompiler);

        $validator->map([1, 2, 3]);

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected list with unique items, got 1 multiple times',
            static fn() => $validator->map([1, 2, 1]),
        );
    }

    public function testUniqueItemsStringValidator(): void
    {
        $mapperCompiler = new MapList(new MapString());
        $validatorCompiler = new AssertUniqueItems();
        $validator = $this->compileValidator('UniqueItemsStringValidator', $mapperCompiler, $validatorCompiler);

        $validator->map(['abc', 'def', 'fg']);

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected list with unique items, got "def" multiple times',
            static fn() => $validator->map(['abc', 'def', 'def', 'fgq']),
        );
    }

}
