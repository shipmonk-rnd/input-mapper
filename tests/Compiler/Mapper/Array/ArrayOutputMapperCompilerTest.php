<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Array;

use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use ShipMonk\InputMapper\Compiler\Mapper\Output\ArrayOutputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\PassthroughMapperCompiler;
use ShipMonk\InputMapperTests\Compiler\Mapper\OutputMapperCompilerTestCase;

class ArrayOutputMapperCompilerTest extends OutputMapperCompilerTestCase
{

    public function testCompile(): void
    {
        $keyMapperCompiler = new PassthroughMapperCompiler(new IdentifierTypeNode('string'));
        $valueMapperCompiler = new PassthroughMapperCompiler(new IdentifierTypeNode('int'));
        $mapperCompiler = new ArrayOutputMapperCompiler($keyMapperCompiler, $valueMapperCompiler);
        $mapper = $this->compileOutputMapper('StringToIntArray', $mapperCompiler);

        self::assertSame([], $mapper->map([]));
        self::assertSame(['a' => 1], $mapper->map(['a' => 1]));
        self::assertSame(['a' => 1, 'b' => 2], $mapper->map(['a' => 1, 'b' => 2]));
    }

}
