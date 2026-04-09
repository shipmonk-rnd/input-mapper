<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Wrapper;

use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use ShipMonk\InputMapper\Compiler\Mapper\Output\EnumOutputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Output\ListOutputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Output\NullableOutputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\PassthroughMapperCompiler;
use ShipMonk\InputMapperTests\Compiler\Mapper\MapperCompilerTestCase;
use ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data\SuitEnum;

class NullableOutputMapperCompilerTest extends MapperCompilerTestCase
{

    public function testCompile(): void
    {
        $mapperCompiler = new NullableOutputMapperCompiler(new PassthroughMapperCompiler(new IdentifierTypeNode('int')));
        $mapper = $this->compileOutputMapper('NullableInt', $mapperCompiler);

        self::assertNull($mapper->map(null));
        self::assertSame(1, $mapper->map(1));
        self::assertSame(2, $mapper->map(2));
    }

    public function testCompileWithMixed(): void
    {
        $mapperCompiler = new NullableOutputMapperCompiler(new PassthroughMapperCompiler(new IdentifierTypeNode('mixed')));
        $mapper = $this->compileOutputMapper('NullableMixed', $mapperCompiler);

        self::assertNull($mapper->map(null));
        self::assertSame(1, $mapper->map(1));
        self::assertSame('A', $mapper->map('A'));
    }

    public function testCompileWithExpressionOnlyInnerMapper(): void
    {
        $mapperCompiler = new NullableOutputMapperCompiler(new EnumOutputMapperCompiler(SuitEnum::class));
        $mapper = $this->compileOutputMapper('NullableEnum', $mapperCompiler);

        self::assertNull($mapper->map(null));
        self::assertSame('H', $mapper->map(SuitEnum::Hearts));
        self::assertSame('S', $mapper->map(SuitEnum::Spades));
    }

    public function testCompileWithInnerMapperThatHasStatements(): void
    {
        $mapperCompiler = new NullableOutputMapperCompiler(
            new ListOutputMapperCompiler(new EnumOutputMapperCompiler(SuitEnum::class)),
        );
        $mapper = $this->compileOutputMapper('NullableEnumList', $mapperCompiler);

        self::assertNull($mapper->map(null));
        self::assertSame(['H', 'D'], $mapper->map([SuitEnum::Hearts, SuitEnum::Diamonds]));
        self::assertSame([], $mapper->map([]));
    }

}
