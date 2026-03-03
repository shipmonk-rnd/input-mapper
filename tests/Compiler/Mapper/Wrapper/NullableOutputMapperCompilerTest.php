<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Wrapper;

use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use ShipMonk\InputMapper\Compiler\Mapper\Output\NullableOutputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\PassthroughMapperCompiler;
use ShipMonk\InputMapperTests\Compiler\Mapper\OutputMapperCompilerTestCase;

class NullableOutputMapperCompilerTest extends OutputMapperCompilerTestCase
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

}
