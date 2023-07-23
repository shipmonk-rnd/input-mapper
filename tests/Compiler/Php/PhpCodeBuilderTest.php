<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Php;

use BackedEnum;
use DateTimeImmutable;
use DateTimeInterface;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPUnit\Framework\Attributes\DataProvider;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonkTests\InputMapper\InputMapperTestCase;

class PhpCodeBuilderTest extends InputMapperTestCase
{

    /**
     * @param list<class-string> $expectedClassImports
     */
    #[DataProvider('provideImportTypeData')]
    public function testImportType(TypeNode $type, array $expectedClassImports): void
    {
        $builder = new PhpCodeBuilder();
        $builder->importType($type);

        $actualClassImports = [];

        foreach ($builder->getImports('App') as $uses) {
            foreach ($uses->uses as $use) {
                $actualClassImports[] = $use->name->toString();
            }
        }

        self::assertSame($expectedClassImports, $actualClassImports);
    }

    /**
     * @return iterable<array{TypeNode, list<class-string>}>
     */
    public static function provideImportTypeData(): iterable
    {
        yield [
            new IdentifierTypeNode('string'),
            [],
        ];

        yield [
            new IdentifierTypeNode(DateTimeImmutable::class),
            [DateTimeImmutable::class],
        ];

        yield [
            new IdentifierTypeNode(DateTimeInterface::class),
            [DateTimeInterface::class],
        ];

        yield [
            new GenericTypeNode(
                new IdentifierTypeNode('iterable'),
                [new IdentifierTypeNode(DateTimeInterface::class), new IdentifierTypeNode(BackedEnum::class)],
            ),
            [BackedEnum::class, DateTimeInterface::class],
        ];
    }

}
