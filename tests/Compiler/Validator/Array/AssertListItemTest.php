<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Validator\Array;

use PhpParser\Node\Expr;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use ShipMonk\InputMapper\Compiler\Mapper\Array\MapList;
use ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapInt;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Compiler\Validator\Array\AssertListItem;
use ShipMonk\InputMapper\Compiler\Validator\Int\AssertIntMultipleOf;
use ShipMonk\InputMapper\Compiler\Validator\Int\AssertPositiveInt;
use ShipMonk\InputMapper\Compiler\Validator\ValidatorCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonkTests\InputMapper\Compiler\Validator\ValidatorCompilerTestCase;

class AssertListItemTest extends ValidatorCompilerTestCase
{

    public function testListItemValidator(): void
    {
        $mapperCompiler = new MapList(new MapInt());
        $validatorCompiler = new AssertListItem([new AssertPositiveInt()]);
        $validator = $this->compileValidator('ListItemValidator', $mapperCompiler, $validatorCompiler);

        $validator->map([]);
        $validator->map([1, 2, 3]);

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /2: Expected value greater than 0, got 0',
            static fn() => $validator->map([1, 2, 0]),
        );
    }

    public function testListItemValidatorWithMultipleValidators(): void
    {
        $mapperCompiler = new MapList(new MapInt());
        $validatorCompiler = new AssertListItem([new AssertPositiveInt(), new AssertIntMultipleOf(5)]);
        $validator = $this->compileValidator('ListItemValidatorWithMultipleValidators', $mapperCompiler, $validatorCompiler);

        $validator->map([]);
        $validator->map([5, 10, 15]);

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /2: Expected value greater than 0, got 0',
            static fn() => $validator->map([5, 10, 0]),
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /2: Expected multiple of 5, got 3',
            static fn() => $validator->map([5, 10, 3]),
        );
    }

    public function testInnerValidatorIsCalledWithCorrectItemType(): void
    {
        $itemValidator = $this->createMock(ValidatorCompiler::class);

        $itemValidator->expects(self::once())
            ->method('compile')
            ->with(
                self::isInstanceOf(Expr::class),
                self::equalTo(new IdentifierTypeNode('int')),
                self::isInstanceOf(Expr::class),
                self::isInstanceOf(PhpCodeBuilder::class),
            );

        $itemValidator->expects(self::exactly(3))
            ->method('getInputType')
            ->willReturn(new IdentifierTypeNode('int'));

        $mapperCompiler = new MapList(new MapInt());
        $validatorCompiler = new AssertListItem([$itemValidator]);
        $this->compileValidator('ListItemValidatorCalledWithCorrectItemType', $mapperCompiler, $validatorCompiler);
    }

}
