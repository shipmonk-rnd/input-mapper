<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper;

use ReflectionClass;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Compiler\Php\PhpCodePrinter;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use ShipMonk\InputMapperTests\InputMapperTestCase;
use function assert;
use function class_exists;
use function is_a;
use function str_replace;
use function strrpos;
use function strtr;
use function substr;
use function ucfirst;

abstract class MapperCompilerTestCase extends InputMapperTestCase
{

    /**
     * @param array<class-string, MapperCompiler> $providedMapperCompilers
     * @param list<Mapper<mixed, mixed>> $genericInnerMappers
     * @return Mapper<mixed, mixed>
     */
    protected function compileMapper(
        string $name,
        MapperCompiler $mapperCompiler,
        array $providedMapperCompilers = [],
        array $genericInnerMappers = [],
    ): Mapper
    {
        $testCaseReflection = new ReflectionClass($this);

        $mapperShortClassName = ucfirst($name) . 'Mapper';
        $mapperNamespace = $testCaseReflection->getNamespaceName() . '\\Data';
        $mapperClassName = "{$mapperNamespace}\\{$mapperShortClassName}";

        $mapperDir = strtr(str_replace('ShipMonk\InputMapperTests', __DIR__ . '/../..', $mapperNamespace), '\\', '/');
        $mapperPath = "{$mapperDir}/{$mapperShortClassName}.php";

        $builder = new PhpCodeBuilder();
        $printer = new PhpCodePrinter();
        $mapperCode = $printer->prettyPrintFile($builder->mapperFile($mapperClassName, $mapperCompiler));
        self::assertSnapshot($mapperPath, $mapperCode);

        if (!class_exists($mapperClassName, autoload: false)) {
            require $mapperPath;
        }

        $mapperProvider = $this->createMock(MapperProvider::class);

        $mapperProvider->expects(self::any())->method('getInputMapper')->willReturnCallback(
            function (string $inputClassName, array $genericInnerMappers = []) use ($name, $providedMapperCompilers): Mapper {
                /** @var list<Mapper<mixed, mixed>> $genericInnerMappers */
                return $this->compileMapper($name . '__' . $this->toShortClassName($inputClassName), $providedMapperCompilers[$inputClassName], [], $genericInnerMappers);
            },
        );

        assert(is_a($mapperClassName, Mapper::class, true));
        return new $mapperClassName($mapperProvider, $genericInnerMappers);
    }

    /**
     * @param array<class-string, MapperCompiler> $providedMapperCompilers
     * @param list<Mapper<mixed, mixed>> $genericInnerMappers
     * @return Mapper<mixed, mixed>
     */
    protected function compileOutputMapper(
        string $name,
        MapperCompiler $mapperCompiler,
        array $providedMapperCompilers = [],
        array $genericInnerMappers = [],
    ): Mapper
    {
        $testCaseReflection = new ReflectionClass($this);

        $mapperShortClassName = ucfirst($name) . 'OutputMapper';
        $mapperNamespace = $testCaseReflection->getNamespaceName() . '\\Data';
        $mapperClassName = "{$mapperNamespace}\\{$mapperShortClassName}";

        $mapperDir = strtr(str_replace('ShipMonk\InputMapperTests', __DIR__ . '/../..', $mapperNamespace), '\\', '/');
        $mapperPath = "{$mapperDir}/{$mapperShortClassName}.php";

        $builder = new PhpCodeBuilder();
        $printer = new PhpCodePrinter();
        $mapperCode = $printer->prettyPrintFile($builder->mapperFile($mapperClassName, $mapperCompiler));
        self::assertSnapshot($mapperPath, $mapperCode);

        if (!class_exists($mapperClassName, autoload: false)) {
            require $mapperPath;
        }

        $mapperProvider = $this->createMock(MapperProvider::class);

        $mapperProvider->expects(self::any())->method('getOutputMapper')->willReturnCallback(
            function (string $inputClassName, array $genericInnerMappers = []) use ($name, $providedMapperCompilers): Mapper {
                /** @var list<Mapper<mixed, mixed>> $genericInnerMappers */
                return $this->compileOutputMapper($name . '__' . $this->toShortClassName($inputClassName), $providedMapperCompilers[$inputClassName], [], $genericInnerMappers);
            },
        );

        assert(is_a($mapperClassName, Mapper::class, true));
        return new $mapperClassName($mapperProvider, $genericInnerMappers);
    }

    private function toShortClassName(string $className): string
    {
        $pos = strrpos($className, '\\');
        return $pos === false ? $className : substr($className, $pos + 1);
    }

}
