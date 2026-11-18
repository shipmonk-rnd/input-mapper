<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper;

use ReflectionClass;
use ReflectionObject;
use ShipMonk\InputMapper\Compiler\Attribute\ArrayShapeItemMapping;
use ShipMonk\InputMapper\Compiler\Attribute\MapArray;
use ShipMonk\InputMapper\Compiler\Attribute\MapArrayShape;
use ShipMonk\InputMapper\Compiler\Attribute\MapBool;
use ShipMonk\InputMapper\Compiler\Attribute\MapChain;
use ShipMonk\InputMapper\Compiler\Attribute\MapDate;
use ShipMonk\InputMapper\Compiler\Attribute\MapDateTimeImmutable;
use ShipMonk\InputMapper\Compiler\Attribute\MapDefaultValue;
use ShipMonk\InputMapper\Compiler\Attribute\MapDelegate;
use ShipMonk\InputMapper\Compiler\Attribute\MapDiscriminatedObject;
use ShipMonk\InputMapper\Compiler\Attribute\MapEnum;
use ShipMonk\InputMapper\Compiler\Attribute\MapFloat;
use ShipMonk\InputMapper\Compiler\Attribute\MapInt;
use ShipMonk\InputMapper\Compiler\Attribute\MapList;
use ShipMonk\InputMapper\Compiler\Attribute\MapMixed;
use ShipMonk\InputMapper\Compiler\Attribute\MapNullable;
use ShipMonk\InputMapper\Compiler\Attribute\MapObject;
use ShipMonk\InputMapper\Compiler\Attribute\MapOptional;
use ShipMonk\InputMapper\Compiler\Attribute\MapString;
use ShipMonk\InputMapper\Compiler\Attribute\MapValidated;
use ShipMonk\InputMapper\Compiler\Mapper\InputMapperCompilerProvider;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompilerProvider;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompilerProviderUtils;
use ShipMonk\InputMapper\Compiler\Mapper\OutputMapperCompilerProvider;
use ShipMonk\InputMapper\Compiler\Type\GenericTypeParameter;
use ShipMonk\InputMapper\Compiler\Validator\Array\AssertListLength;
use ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data\SuitEnum;
use ShipMonk\InputMapperTests\InputMapperTestCase;
use stdClass;
use function array_diff_key;
use function array_map;
use function basename;
use function get_debug_type;
use function glob;
use function is_array;
use function is_object;
use function spl_object_id;

class CompositeMapperCompilerProviderTest extends InputMapperTestCase
{

    /**
     * Guards that no provider nested anywhere in provider properties is missed
     * by a CompositeMapperCompilerProvider::getInnerMapperCompilerProviders() implementation.
     */
    public function testTypedTraversalMatchesReflectiveTraversal(): void
    {
        $root = self::createProviderGraph();

        $typed = [];

        foreach (MapperCompilerProviderUtils::iterate($root) as $provider) {
            $typed[spl_object_id($provider)] = $provider;
        }

        $reflective = [];
        $visited = [];
        self::collectProvidersReflectively($root, $reflective, $visited);

        $missed = array_map(get_debug_type(...), array_diff_key($reflective, $typed));
        self::assertSame([], $missed, 'Some providers are reachable via reflection, but not exposed through CompositeMapperCompilerProvider::getInnerMapperCompilerProviders()');
    }

    public function testProviderGraphContainsAllAttributeProviders(): void
    {
        $classesInGraph = [];

        foreach (MapperCompilerProviderUtils::iterate(self::createProviderGraph()) as $provider) {
            $classesInGraph[$provider::class] = true;
        }

        $attributeFiles = glob(__DIR__ . '/../../../src/Compiler/Attribute/*.php');
        self::assertNotFalse($attributeFiles);
        self::assertNotSame([], $attributeFiles);

        foreach ($attributeFiles as $file) {
            /** @var class-string $className */
            $className = 'ShipMonk\\InputMapper\\Compiler\\Attribute\\' . basename($file, '.php');
            $reflection = new ReflectionClass($className);

            if (
                !$reflection->implementsInterface(InputMapperCompilerProvider::class)
                && !$reflection->implementsInterface(OutputMapperCompilerProvider::class)
            ) {
                continue;
            }

            self::assertArrayHasKey($className, $classesInGraph, "Add {$className} to the provider graph in this test, so that its inner providers are verified");
        }
    }

    private static function createProviderGraph(): MapperCompilerProvider
    {
        return new MapChain([
            new MapArray(new MapString(), new MapMixed()),
            new MapArrayShape(
                items: [
                    new ArrayShapeItemMapping(key: 'size', mapper: new MapInt()),
                    new ArrayShapeItemMapping(key: 'input', mapper: new MapDelegate(stdClass::class), optional: true),
                ],
            ),
            new MapBool(),
            new MapDate(),
            new MapDateTimeImmutable(),
            new MapDefaultValue(new MapFloat(), defaultValue: 1.5),
            new MapDiscriminatedObject(
                className: stdClass::class,
                discriminatorKeyName: 'type',
                subtypeProviders: ['a' => new MapDelegate(stdClass::class, [new MapMixed()])],
                genericParameters: [new GenericTypeParameter('T')],
            ),
            new MapObject(
                className: stdClass::class,
                constructorArgsProviders: ['foo' => new MapOptional(new MapNullable(new MapList(new MapEnum(SuitEnum::class, new MapString()))))],
                allowExtraKeys: true,
                propertyProviders: ['bar' => ['bar', new MapValidated(new MapString(), [new AssertListLength(min: 1)])]],
                genericParameters: [new GenericTypeParameter('V')],
            ),
        ]);
    }

    /**
     * @param array<int, InputMapperCompilerProvider|OutputMapperCompilerProvider> $found
     * @param array<int, true> $visited
     */
    private static function collectProvidersReflectively(
        mixed $value,
        array &$found,
        array &$visited,
    ): void
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                self::collectProvidersReflectively($item, $found, $visited);
            }

            return;
        }

        if (!is_object($value) || isset($visited[spl_object_id($value)])) {
            return;
        }

        $visited[spl_object_id($value)] = true;

        if ($value instanceof InputMapperCompilerProvider || $value instanceof OutputMapperCompilerProvider) {
            $found[spl_object_id($value)] = $value;
        }

        for ($class = new ReflectionObject($value); $class !== false; $class = $class->getParentClass()) {
            foreach ($class->getProperties() as $property) {
                if ($property->isStatic()) {
                    continue;
                }

                self::collectProvidersReflectively($property->getValue($value), $found, $visited);
            }
        }
    }

}
