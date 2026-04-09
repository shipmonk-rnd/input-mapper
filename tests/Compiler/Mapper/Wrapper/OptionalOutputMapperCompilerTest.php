<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Wrapper;

use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use ShipMonk\InputMapper\Compiler\Mapper\Output\OptionalOutputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\PassthroughMapperCompiler;
use ShipMonk\InputMapper\Runtime\Optional;
use ShipMonk\InputMapperTests\Compiler\Mapper\MapperCompilerTestCase;

class OptionalOutputMapperCompilerTest extends MapperCompilerTestCase
{

    public function testCompile(): void
    {
        $mapperCompiler = new OptionalOutputMapperCompiler(new PassthroughMapperCompiler(new IdentifierTypeNode('int')));
        $mapper = $this->compileOutputMapper('OptionalInt', $mapperCompiler);

        self::assertSame(1, $mapper->map(Optional::of(1)));
        self::assertSame(42, $mapper->map(Optional::of(42)));
    }

}
