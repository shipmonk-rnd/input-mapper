<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Php;

use BackedEnum;
use DateTimeImmutable;
use DateTimeInterface;
use LogicException;
use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use PhpParser\Node\Expr\BinaryOp\BooleanOr;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPUnit\Framework\Attributes\DataProvider;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Compiler\Php\PhpCodePrinter;
use ShipMonk\InputMapperTests\InputMapperTestCase;

class PhpCodeBuilderTest extends InputMapperTestCase
{

    /**
     * @param list<class-string> $expectedClassImports
     */
    #[DataProvider('provideImportTypeData')]
    public function testImportType(
        TypeNode $type,
        array $expectedClassImports,
    ): void
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

    public function testArray(): void
    {
        $builder = new PhpCodeBuilder();
        $printer = new PhpCodePrinter();

        $array = $builder->array([]);
        self::assertSame(Array_::KIND_SHORT, $array->getAttribute('kind'));
        self::assertCount(0, $array->items);
        self::assertSame('[]', $printer->prettyPrintExpr($array));

        $arrayWithItems = $builder->array([
            $builder->arrayItem($builder->val(1), null),
            $builder->arrayItem($builder->val('value'), $builder->val('key')),
        ]);
        self::assertCount(2, $arrayWithItems->items);
        self::assertSame("[1, 'key' => 'value']", $printer->prettyPrintExpr($arrayWithItems));
    }

    public function testArrayItem(): void
    {
        $builder = new PhpCodeBuilder();
        $printer = new PhpCodePrinter();

        $item = $builder->arrayItem($builder->val('value'), null);
        self::assertNull($item->key);
        self::assertSame("['value']", $printer->prettyPrintExpr($builder->array([$item])));

        $itemWithKey = $builder->arrayItem($builder->val('value'), $builder->val('key'));
        self::assertNotNull($itemWithKey->key);
        self::assertSame("['key' => 'value']", $printer->prettyPrintExpr($builder->array([$itemWithKey])));
    }

    public function testArrayImmutableAppendToEmptyArray(): void
    {
        $builder = new PhpCodeBuilder();
        $printer = new PhpCodePrinter();

        $emptyArray = $builder->array([]);
        $result = $builder->arrayImmutableAppend($emptyArray, $builder->val('new'));
        self::assertSame("['new']", $printer->prettyPrintExpr($result));
    }

    public function testArrayImmutableAppendToVariable(): void
    {
        $builder = new PhpCodeBuilder();
        $printer = new PhpCodePrinter();

        $variable = $builder->var('items');
        $result = $builder->arrayImmutableAppend($variable, $builder->val('new'));
        self::assertSame("[...\$items, 'new']", $printer->prettyPrintExpr($result));
    }

    public function testArrayDimFetch(): void
    {
        $builder = new PhpCodeBuilder();
        $printer = new PhpCodePrinter();

        $fetch = $builder->arrayDimFetch($builder->var('arr'), $builder->val('key'));
        self::assertNotNull($fetch->dim);
        self::assertSame("\$arr['key']", $printer->prettyPrintExpr($fetch));

        $fetchWithoutDim = $builder->arrayDimFetch($builder->var('arr'));
        self::assertNull($fetchWithoutDim->dim);
        self::assertSame('$arr[]', $printer->prettyPrintExpr($fetchWithoutDim));
    }

    public function testNot(): void
    {
        $builder = new PhpCodeBuilder();
        $printer = new PhpCodePrinter();

        $not = $builder->not($builder->val(true));
        self::assertSame('!true', $printer->prettyPrintExpr($not));
    }

    public function testAndSingleOperand(): void
    {
        $builder = new PhpCodeBuilder();
        $printer = new PhpCodePrinter();

        $result = $builder->and($builder->val(true));
        self::assertNotInstanceOf(BooleanAnd::class, $result);
        self::assertSame('true', $printer->prettyPrintExpr($result));
    }

    public function testAndMultipleOperands(): void
    {
        $builder = new PhpCodeBuilder();
        $printer = new PhpCodePrinter();

        $result = $builder->and($builder->val(true), $builder->val(false));
        self::assertSame('true && false', $printer->prettyPrintExpr($result));

        $result3 = $builder->and($builder->val(true), $builder->val(false), $builder->var('a'));
        self::assertSame('true && false && $a', $printer->prettyPrintExpr($result3));
    }

    public function testOrSingleOperand(): void
    {
        $builder = new PhpCodeBuilder();
        $printer = new PhpCodePrinter();

        $result = $builder->or($builder->val(true));
        self::assertNotInstanceOf(BooleanOr::class, $result);
        self::assertSame('true', $printer->prettyPrintExpr($result));
    }

    public function testOrMultipleOperands(): void
    {
        $builder = new PhpCodeBuilder();
        $printer = new PhpCodePrinter();

        $result = $builder->or($builder->val(true), $builder->val(false));
        self::assertSame('true || false', $printer->prettyPrintExpr($result));

        $result3 = $builder->or($builder->val(true), $builder->val(false), $builder->var('a'));
        self::assertSame('true || false || $a', $printer->prettyPrintExpr($result3));
    }

    public function testSame(): void
    {
        $builder = new PhpCodeBuilder();
        $printer = new PhpCodePrinter();

        $same = $builder->same($builder->val(1), $builder->val(1));
        self::assertSame('1 === 1', $printer->prettyPrintExpr($same));
    }

    public function testNotSame(): void
    {
        $builder = new PhpCodeBuilder();
        $printer = new PhpCodePrinter();

        $notSame = $builder->notSame($builder->val(1), $builder->val(2));
        self::assertSame('1 !== 2', $printer->prettyPrintExpr($notSame));
    }

    public function testLt(): void
    {
        $builder = new PhpCodeBuilder();
        $printer = new PhpCodePrinter();

        $lt = $builder->lt($builder->val(1), $builder->val(2));
        self::assertSame('1 < 2', $printer->prettyPrintExpr($lt));
    }

    public function testLte(): void
    {
        $builder = new PhpCodeBuilder();
        $printer = new PhpCodePrinter();

        $lte = $builder->lte($builder->val(1), $builder->val(2));
        self::assertSame('1 <= 2', $printer->prettyPrintExpr($lte));
    }

    public function testGt(): void
    {
        $builder = new PhpCodeBuilder();
        $printer = new PhpCodePrinter();

        $gt = $builder->gt($builder->val(2), $builder->val(1));
        self::assertSame('2 > 1', $printer->prettyPrintExpr($gt));
    }

    public function testGte(): void
    {
        $builder = new PhpCodeBuilder();
        $printer = new PhpCodePrinter();

        $gte = $builder->gte($builder->val(2), $builder->val(1));
        self::assertSame('2 >= 1', $printer->prettyPrintExpr($gte));
    }

    public function testInstanceOf(): void
    {
        $builder = new PhpCodeBuilder();
        $printer = new PhpCodePrinter();

        $instanceof = $builder->instanceOf($builder->var('obj'), 'SomeClass');
        self::assertSame('$obj instanceof SomeClass', $printer->prettyPrintExpr($instanceof));
    }

    public function testTernary(): void
    {
        $builder = new PhpCodeBuilder();
        $printer = new PhpCodePrinter();

        $ternary = $builder->ternary(
            $builder->val(true),
            $builder->val('yes'),
            $builder->val('no'),
        );
        self::assertSame("true ? 'yes' : 'no'", $printer->prettyPrintExpr($ternary));
    }

    public function testIfSimple(): void
    {
        $builder = new PhpCodeBuilder();

        $if = $builder->if(
            $builder->val(true),
            [$builder->return($builder->val(1))],
        );
        self::assertNull($if->else);
        self::assertCount(0, $if->elseifs);
        self::assertCodeEquals(
            [$if],
            <<<'PHP'
            if (true) {
                return 1;
            }

            PHP,
        );
    }

    public function testIfWithElse(): void
    {
        $builder = new PhpCodeBuilder();

        $if = $builder->if(
            $builder->val(true),
            [$builder->return($builder->val(1))],
            [$builder->return($builder->val(2))],
        );
        self::assertNotNull($if->else);
        self::assertCount(0, $if->elseifs);
        self::assertCodeEquals(
            [$if],
            <<<'PHP'
            if (true) {
                return 1;
            } else {
                return 2;
            }

            PHP,
        );
    }

    public function testIfWithNestedElseIfConversion(): void
    {
        $builder = new PhpCodeBuilder();

        $nestedIf = $builder->if(
            $builder->val(false),
            [$builder->return($builder->val(2))],
            [$builder->return($builder->val(3))],
        );

        $if = $builder->if(
            $builder->val(true),
            [$builder->return($builder->val(1))],
            [$nestedIf],
        );
        self::assertCount(1, $if->elseifs);
        self::assertNotNull($if->else);
        self::assertCodeEquals(
            [$if],
            <<<'PHP'
            if (true) {
                return 1;
            } elseif (false) {
                return 2;
            } else {
                return 3;
            }

            PHP,
        );
    }

    public function testMatch(): void
    {
        $builder = new PhpCodeBuilder();
        $printer = new PhpCodePrinter();

        $match = $builder->match(
            $builder->var('value'),
            [
                $builder->matchArm($builder->val(1), $builder->val('one')),
                $builder->matchArm(null, $builder->val('default')),
            ],
        );
        self::assertCount(2, $match->arms);
        self::assertSame(
            <<<'PHP'
            match ($value) {
                1 => 'one',
                default => 'default',
            }
            PHP,
            $printer->prettyPrintExpr($match),
        );
    }

    public function testMatchArm(): void
    {
        $builder = new PhpCodeBuilder();

        $arm = $builder->matchArm($builder->val(1), $builder->val('one'));
        self::assertNotNull($arm->conds);

        $defaultArm = $builder->matchArm(null, $builder->val('default'));
        self::assertNull($defaultArm->conds);
    }

    public function testForeach(): void
    {
        $builder = new PhpCodeBuilder();

        $foreach = $builder->foreach(
            $builder->var('items'),
            $builder->var('value'),
            $builder->var('key'),
            [$builder->return($builder->var('value'))],
        );
        self::assertCodeEquals(
            [$foreach],
            <<<'PHP'
            foreach ($items as $key => $value) {
                return $value;
            }

            PHP,
        );
    }

    public function testFor(): void
    {
        $builder = new PhpCodeBuilder();

        $for = $builder->for(
            $builder->assignExpr($builder->var('i'), $builder->val(0)),
            $builder->lt($builder->var('i'), $builder->val(10)),
            $builder->preIncrement($builder->var('i')),
            [$builder->return($builder->var('i'))],
        );
        self::assertCodeEquals(
            [$for],
            <<<'PHP'
            for ($i = 0; $i < 10; ++$i) {
                return $i;
            }
            PHP,
        );
    }

    public function testPreIncrement(): void
    {
        $builder = new PhpCodeBuilder();
        $printer = new PhpCodePrinter();

        $preInc = $builder->preIncrement($builder->var('i'));
        self::assertSame('++$i', $printer->prettyPrintExpr($preInc));
    }

    public function testPlus(): void
    {
        $builder = new PhpCodeBuilder();
        $printer = new PhpCodePrinter();

        $plus = $builder->plus($builder->val(1), $builder->val(2));
        self::assertSame('1 + 2', $printer->prettyPrintExpr($plus));
    }

    public function testThrowNew(): void
    {
        $builder = new PhpCodeBuilder();

        $throw = $builder->throwNew('RuntimeException', ['Error message']);
        self::assertCodeEquals(
            [$throw],
            "throw new RuntimeException('Error message');",
        );
    }

    public function testThrow(): void
    {
        $builder = new PhpCodeBuilder();

        $throw = $builder->throw($builder->new('Exception', []));
        self::assertCodeEquals(
            [$throw],
            'throw new Exception();',
        );
    }

    public function testThrowExpr(): void
    {
        $builder = new PhpCodeBuilder();
        $printer = new PhpCodePrinter();

        $throwExpr = $builder->throwExpr($builder->new('Exception', []));
        self::assertSame('throw new Exception()', $printer->prettyPrintExpr($throwExpr));
    }

    public function testAssign(): void
    {
        $builder = new PhpCodeBuilder();

        $assign = $builder->assign($builder->var('x'), $builder->val(1));
        self::assertCodeEquals(
            [$assign],
            '$x = 1;',
        );
    }

    public function testAssignExpr(): void
    {
        $builder = new PhpCodeBuilder();
        $printer = new PhpCodePrinter();

        $assignExpr = $builder->assignExpr($builder->var('x'), $builder->val(1));
        self::assertSame('$x = 1', $printer->prettyPrintExpr($assignExpr));
    }

    public function testReturn(): void
    {
        $builder = new PhpCodeBuilder();

        $return = $builder->return($builder->val(42));
        self::assertCodeEquals(
            [$return],
            'return 42;',
        );
    }

    public function testUniqConstantName(): void
    {
        $builder = new PhpCodeBuilder();

        $name1 = $builder->uniqConstantName('CONST', 'value1');
        self::assertSame('CONST', $name1);
        $builder->addConstant($name1, 'value1');

        $name2 = $builder->uniqConstantName('CONST', 'value1');
        self::assertSame('CONST', $name2);

        $name3 = $builder->uniqConstantName('CONST', 'value2');
        self::assertSame('CONST2', $name3);
    }

    public function testUniqMethodName(): void
    {
        $builder = new PhpCodeBuilder();

        $name1 = $builder->uniqMethodName('method');
        self::assertSame('method', $name1);

        $builder->withVariableScope(static function () use ($builder, $name1): void {
            $builder->addMethod(
                $builder->method($name1)
                    ->makePublic()
                    ->getNode(),
            );
        });

        $name2 = $builder->uniqMethodName('method');
        self::assertSame('method2', $name2);
    }

    public function testUniqVariableNameOutsideScope(): void
    {
        $builder = new PhpCodeBuilder();

        self::assertException(
            LogicException::class,
            'Unable to create unique variable name outside of variable scope',
            static fn () => $builder->uniqVariableName('var'),
        );
    }

    public function testUniqVariableNameInsideScope(): void
    {
        $builder = new PhpCodeBuilder();

        $builder->withVariableScope(static function () use ($builder): void {
            $name1 = $builder->uniqVariableName('var');
            self::assertSame('var', $name1);

            $name2 = $builder->uniqVariableName('var');
            self::assertSame('var2', $name2);

            $name3 = $builder->uniqVariableName('other');
            self::assertSame('other', $name3);
        });
    }

    public function testUniqVariableNames(): void
    {
        $builder = new PhpCodeBuilder();

        $builder->withVariableScope(static function () use ($builder): void {
            $names = $builder->uniqVariableNames('a', 'b', 'a');
            self::assertSame(['a', 'b', 'a2'], $names);
        });
    }

    public function testWithVariableScopeNesting(): void
    {
        $builder = new PhpCodeBuilder();

        $builder->withVariableScope(static function () use ($builder): void {
            $outer = $builder->uniqVariableName('var');
            self::assertSame('var', $outer);

            $builder->withVariableScope(static function () use ($builder): void {
                $inner = $builder->uniqVariableName('var');
                self::assertSame('var', $inner);
            });

            $afterNested = $builder->uniqVariableName('var');
            self::assertSame('var2', $afterNested);
        });
    }

    public function testAddConstantDuplicate(): void
    {
        $builder = new PhpCodeBuilder();

        $builder->addConstant('CONST', 'value1');
        $builder->addConstant('CONST', 'value1');

        self::assertException(
            LogicException::class,
            'Constant already exists with different value',
            static fn () => $builder->addConstant('CONST', 'value2'),
        );
    }

    public function testAddMethodDuplicate(): void
    {
        $builder = new PhpCodeBuilder();

        $builder->withVariableScope(static function () use ($builder): void {
            $method = $builder->method('myMethod')->makePublic()->getNode();
            $builder->addMethod($method);

            self::assertException(
                LogicException::class,
                'Method already exists',
                static fn () => $builder->addMethod($builder->method('myMethod')->makePublic()->getNode()),
            );
        });
    }

    public function testImportClass(): void
    {
        $builder = new PhpCodeBuilder();

        $alias1 = $builder->importClass(DateTimeImmutable::class);
        self::assertSame('DateTimeImmutable', $alias1);

        $alias2 = $builder->importClass(DateTimeImmutable::class);
        self::assertSame('DateTimeImmutable', $alias2);

        $alias3 = $builder->importClass(DateTimeInterface::class);
        self::assertSame('DateTimeInterface', $alias3);
    }

    public function testImportClassConflict(): void
    {
        $builder = new PhpCodeBuilder();

        $alias1 = $builder->importClass('App\\Model\\User');
        self::assertSame('User', $alias1);

        $alias2 = $builder->importClass('App\\Entity\\User');
        self::assertSame('User1', $alias2);
    }

    public function testImportFunction(): void
    {
        $builder = new PhpCodeBuilder();

        $alias1 = $builder->importFunction('array_map');
        self::assertSame('array_map', $alias1);

        $alias2 = $builder->importFunction('array_map');
        self::assertSame('array_map', $alias2);
    }

    public function testImportFunctionConflict(): void
    {
        $builder = new PhpCodeBuilder();

        $alias1 = $builder->importFunction('strlen');
        self::assertSame('strlen', $alias1);

        $alias2 = $builder->importFunction('my_strlen');
        self::assertSame('my_strlen', $alias2);
    }

    public function testPhpDocEmpty(): void
    {
        $builder = new PhpCodeBuilder();

        $phpDoc = $builder->phpDoc([]);
        self::assertSame('', $phpDoc);

        $phpDocWithNulls = $builder->phpDoc([null, null]);
        self::assertSame('', $phpDocWithNulls);
    }

    public function testPhpDocSingleLine(): void
    {
        $builder = new PhpCodeBuilder();

        $phpDoc = $builder->phpDoc(['@param string $name']);
        self::assertSame("/**\n * @param string \$name\n */", $phpDoc);
    }

    public function testPhpDocMultipleLines(): void
    {
        $builder = new PhpCodeBuilder();

        $phpDoc = $builder->phpDoc([
            '@param string $name',
            '@return void',
        ]);
        self::assertSame("/**\n * @param string \$name\n * @return void\n */", $phpDoc);
    }

    public function testPhpDocFiltersNulls(): void
    {
        $builder = new PhpCodeBuilder();

        $phpDoc = $builder->phpDoc([
            '@param string $name',
            null,
            '@return void',
        ]);
        self::assertSame("/**\n * @param string \$name\n * @return void\n */", $phpDoc);
    }

    public function testGetGenericParametersEmpty(): void
    {
        $builder = new PhpCodeBuilder();
        self::assertSame([], $builder->getGenericParameters());
    }

    public function testGetImportsSortsAlphabetically(): void
    {
        $builder = new PhpCodeBuilder();

        $builder->importClass('Zebra\\Animal');
        $builder->importClass('Apple\\Fruit');
        $builder->importClass('Banana\\Fruit');

        $imports = $builder->getImports('App');
        self::assertCount(3, $imports);

        $importedNames = [];
        foreach ($imports as $use) {
            foreach ($use->uses as $useUse) {
                $importedNames[] = $useUse->name->toString();
            }
        }

        self::assertSame(['Apple\\Fruit', 'Banana\\Fruit', 'Zebra\\Animal'], $importedNames);
    }

    public function testGetImportsSkipsSameNamespace(): void
    {
        $builder = new PhpCodeBuilder();

        $builder->importClass('App\\MyClass');
        $builder->importClass('Other\\OtherClass');

        $imports = $builder->getImports('App');
        self::assertCount(1, $imports);

        $importedNames = [];
        foreach ($imports as $use) {
            foreach ($use->uses as $useUse) {
                $importedNames[] = $useUse->name->toString();
            }
        }

        self::assertSame(['Other\\OtherClass'], $importedNames);
    }

    public function testGetImportsWithAliases(): void
    {
        $builder = new PhpCodeBuilder();

        $builder->importClass('App\\Model\\User');
        $builder->importClass('App\\Entity\\User');

        $imports = $builder->getImports('Other');

        self::assertCodeEquals(
            $imports,
            <<<'PHP'
            use App\Entity\User as User1;
            use App\Model\User;
            PHP,
        );
    }

    public function testFunctionImportsAreSeparateFromClassImports(): void
    {
        $builder = new PhpCodeBuilder();

        $builder->importClass('App\\MyClass');
        $builder->importFunction('array_map');

        $imports = $builder->getImports('Other');
        self::assertCount(2, $imports);

        self::assertCodeEquals(
            $imports,
            <<<'PHP'
            use App\MyClass;
            use function array_map;
            PHP,
        );
    }

    /**
     * @param list<Node> $nodes
     */
    private static function assertCodeEquals(
        array $nodes,
        string $expectedCode,
    ): void
    {
        $printer = new PhpCodePrinter();
        $actualCode = $printer->prettyPrint($nodes);
        self::assertSame($expectedCode, $actualCode);
    }

}
