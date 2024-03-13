<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Type;

use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPUnit\Framework\Attributes\DataProvider;
use ShipMonk\InputMapper\Compiler\Type\GenericTypeParameter;
use ShipMonk\InputMapper\Compiler\Type\GenericTypeVariance;
use ShipMonkTests\InputMapper\InputMapperTestCase;

class GenericTypeParameterTest extends InputMapperTestCase
{

    #[DataProvider('provideToPhpDocLineData')]
    public function testToPhpDocLine(GenericTypeParameter $parameter, string $expected): void
    {
        self::assertSame($expected, $parameter->toPhpDocLine());
    }

    public static function provideToPhpDocLineData(): iterable
    {
        yield [
            new GenericTypeParameter('T'),
            '@template T',
        ];

        yield [
            new GenericTypeParameter('T', variance: GenericTypeVariance::Covariant),
            '@template-covariant T',
        ];

        yield [
            new GenericTypeParameter('T', variance: GenericTypeVariance::Contravariant),
            '@template-contravariant T',
        ];

        yield [
            new GenericTypeParameter('T', bound: new IdentifierTypeNode('int')),
            '@template T of int',
        ];

        yield [
            new GenericTypeParameter('T', default: new IdentifierTypeNode('int')),
            '@template T = int',
        ];

        yield [
            new GenericTypeParameter('T', variance: GenericTypeVariance::Covariant, bound: new IdentifierTypeNode('int')),
            '@template-covariant T of int',
        ];

        yield [
            new GenericTypeParameter('T', variance: GenericTypeVariance::Contravariant, default: new IdentifierTypeNode('int')),
            '@template-contravariant T = int',
        ];

        yield [
            new GenericTypeParameter('T', variance: GenericTypeVariance::Covariant, bound: new IdentifierTypeNode('int'), default: new IdentifierTypeNode('int')),
            '@template-covariant T of int = int',
        ];

        yield [
            new GenericTypeParameter('T', variance: GenericTypeVariance::Contravariant, bound: new IdentifierTypeNode('int'), default: new IdentifierTypeNode('int')),
            '@template-contravariant T of int = int',
        ];

        yield [
            new GenericTypeParameter('T', variance: GenericTypeVariance::Invariant, bound: new IdentifierTypeNode('int'), default: new IdentifierTypeNode('int')),
            '@template T of int = int',
        ];

        yield [
            new GenericTypeParameter('T', variance: GenericTypeVariance::Invariant, bound: new IdentifierTypeNode('int')),
            '@template T of int',
        ];

        yield [
            new GenericTypeParameter('T', variance: GenericTypeVariance::Invariant, default: new IdentifierTypeNode('int')),
            '@template T = int',
        ];
    }

}
