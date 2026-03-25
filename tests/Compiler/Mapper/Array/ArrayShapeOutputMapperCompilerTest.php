<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Array;

use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use ShipMonk\InputMapper\Compiler\Mapper\Output\ArrayShapeOutputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Output\EnumOutputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\PassthroughMapperCompiler;
use ShipMonk\InputMapperTests\Compiler\Mapper\MapperCompilerTestCase;
use ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data\SuitEnum;

class ArrayShapeOutputMapperCompilerTest extends MapperCompilerTestCase
{

    public function testCompileEmptySealedArrayShape(): void
    {
        $mapperCompiler = new ArrayShapeOutputMapperCompiler([], sealed: true);
        $mapper = $this->compileOutputMapper('EmptySealedArrayShape', $mapperCompiler);

        self::assertSame([], $mapper->map([]));
    }

    public function testCompileEmptyUnsealedArrayShape(): void
    {
        $mapperCompiler = new ArrayShapeOutputMapperCompiler([], sealed: false);
        $mapper = $this->compileOutputMapper('EmptyUnsealedArrayShape', $mapperCompiler);

        self::assertSame([], $mapper->map([]));
    }

    public function testCompileSealedArrayShape(): void
    {
        $items = [
            ['key' => 'a', 'mapper' => new PassthroughMapperCompiler(new IdentifierTypeNode('int')), 'optional' => false],
            ['key' => 'b', 'mapper' => new PassthroughMapperCompiler(new IdentifierTypeNode('string')), 'optional' => true],
        ];

        $mapperCompiler = new ArrayShapeOutputMapperCompiler($items, sealed: true);
        $mapper = $this->compileOutputMapper('SealedArrayShape', $mapperCompiler);

        self::assertSame(['a' => 1, 'b' => '2'], $mapper->map(['a' => 1, 'b' => '2']));
        self::assertSame(['a' => 1], $mapper->map(['a' => 1]));
    }

    public function testCompileUnsealedArrayShape(): void
    {
        $items = [
            ['key' => 'a', 'mapper' => new PassthroughMapperCompiler(new IdentifierTypeNode('int')), 'optional' => false],
        ];

        $mapperCompiler = new ArrayShapeOutputMapperCompiler($items, sealed: false);
        $mapper = $this->compileOutputMapper('UnsealedArrayShape', $mapperCompiler);

        self::assertSame(['a' => 1], $mapper->map(['a' => 1]));
        self::assertSame(['a' => 1], $mapper->map(['a' => 1, 'extra' => 'ignored']));
    }

    public function testCompileWithInlinedExpressionMapper(): void
    {
        $items = [
            ['key' => 'name', 'mapper' => new PassthroughMapperCompiler(new IdentifierTypeNode('string')), 'optional' => false],
            ['key' => 'suit', 'mapper' => new EnumOutputMapperCompiler(SuitEnum::class), 'optional' => false],
        ];

        $mapperCompiler = new ArrayShapeOutputMapperCompiler($items, sealed: true);
        $mapper = $this->compileOutputMapper('ArrayShapeWithInlinedExpression', $mapperCompiler);

        self::assertSame(['name' => 'test', 'suit' => 'H'], $mapper->map(['name' => 'test', 'suit' => SuitEnum::Hearts]));
    }

}
