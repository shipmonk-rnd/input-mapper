<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper;

use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use ShipMonk\InputMapper\Compiler\Mapper\PassthroughMapperCompiler;

class PassthroughMapperCompilerTest extends OutputMapperCompilerTestCase
{

    public function testCompile(): void
    {
        $mapperCompiler = new PassthroughMapperCompiler(new IdentifierTypeNode('int'));
        $mapper = $this->compileOutputMapper('Passthrough', $mapperCompiler);

        self::assertSame(1, $mapper->map(1));
        self::assertSame(2, $mapper->map(2));
    }

    public function testCompileMixed(): void
    {
        $mapperCompiler = new PassthroughMapperCompiler(new IdentifierTypeNode('mixed'));
        $mapper = $this->compileOutputMapper('PassthroughMixed', $mapperCompiler);

        self::assertSame(1, $mapper->map(1));
        self::assertSame('hello', $mapper->map('hello'));
        self::assertNull($mapper->map(null));
        self::assertSame([1, 2, 3], $mapper->map([1, 2, 3]));
    }

    public function testTypes(): void
    {
        $mapperCompiler = new PassthroughMapperCompiler(new IdentifierTypeNode('string'));
        self::assertSame('string', (string) $mapperCompiler->getInputType());
        self::assertSame('string', (string) $mapperCompiler->getOutputType());
    }

}
