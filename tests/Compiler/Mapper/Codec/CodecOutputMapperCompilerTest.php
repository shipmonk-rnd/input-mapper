<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Codec;

use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use ShipMonk\InputMapper\Compiler\Mapper\Codec\CodecOutputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\Output\ArrayShapeOutputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\PassthroughMapperCompiler;
use ShipMonk\InputMapperTests\Compiler\Mapper\MapperCompilerTestCase;
use ShipMonk\InputMapperTests\Runtime\Data\MoneyCodec;
use ShipMonk\InputMapperTests\Runtime\Data\MoneyValue;

class CodecOutputMapperCompilerTest extends MapperCompilerTestCase
{

    public function testCompile(): void
    {
        $mapper = $this->compileOutputMapper(
            'CodecMoney',
            new CodecOutputMapperCompiler(MoneyCodec::class, $this->createIntermediateOutputMapper(), MoneyValue::class),
            codecs: [MoneyCodec::class => new MoneyCodec()],
        );

        $result = $mapper->map(new MoneyValue('EUR', 500));

        self::assertSame(['currency' => 'EUR', 'cents' => 500], $result);
    }

    private function createIntermediateOutputMapper(): ArrayShapeOutputMapperCompiler
    {
        return new ArrayShapeOutputMapperCompiler([
            ['key' => 'currency', 'mapper' => new PassthroughMapperCompiler(new IdentifierTypeNode('string')), 'optional' => false],
            ['key' => 'cents', 'mapper' => new PassthroughMapperCompiler(new IdentifierTypeNode('int')), 'optional' => false],
        ]);
    }

}
