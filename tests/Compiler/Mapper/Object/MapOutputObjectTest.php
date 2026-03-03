<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Object;

use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use ShipMonk\InputMapper\Compiler\Mapper\Output\ObjectOutputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\PassthroughMapperCompiler;
use ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data\PersonWithSourceKeyInput;
use ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data\SimplePersonInput;
use ShipMonk\InputMapperTests\Compiler\Mapper\OutputMapperCompilerTestCase;

class MapOutputObjectTest extends OutputMapperCompilerTestCase
{

    public function testCompileSimplePerson(): void
    {
        $mapperCompiler = new ObjectOutputMapperCompiler(
            SimplePersonInput::class,
            [
                'id' => ['id', new PassthroughMapperCompiler(new IdentifierTypeNode('int'))],
                'name' => ['name', new PassthroughMapperCompiler(new IdentifierTypeNode('string'))],
            ],
        );

        $mapper = $this->compileOutputMapper('SimplePerson', $mapperCompiler);

        self::assertSame(
            ['id' => 1, 'name' => 'John'],
            $mapper->map(new SimplePersonInput(1, 'John')),
        );

        self::assertSame(
            ['id' => 42, 'name' => 'Jane'],
            $mapper->map(new SimplePersonInput(42, 'Jane')),
        );
    }

    public function testCompileWithSourceKey(): void
    {
        $mapperCompiler = new ObjectOutputMapperCompiler(
            PersonWithSourceKeyInput::class,
            [
                'id' => ['id', new PassthroughMapperCompiler(new IdentifierTypeNode('int'))],
                'name' => ['full_name', new PassthroughMapperCompiler(new IdentifierTypeNode('string'))],
            ],
        );

        $mapper = $this->compileOutputMapper('PersonWithSourceKey', $mapperCompiler);

        self::assertSame(
            ['id' => 1, 'full_name' => 'John'],
            $mapper->map(new PersonWithSourceKeyInput(1, 'John')),
        );
    }

}
