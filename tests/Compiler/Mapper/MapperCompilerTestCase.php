<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper;

use ReflectionClass;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Compiler\Php\PhpCodePrinter;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use ShipMonkTests\InputMapper\InputMapperTestCase;
use function assert;
use function class_exists;
use function str_replace;
use function strrpos;
use function strtr;
use function substr;
use function ucfirst;

abstract class MapperCompilerTestCase extends InputMapperTestCase
{

    /**
     * @param  array<class-string, MapperCompiler> $providedMapperCompilers
     * @param  list<Mapper<mixed>>                 $innerMappers
     * @return Mapper<mixed>
     */
    protected function compileMapper(
        string $name,
        MapperCompiler $mapperCompiler,
        array $providedMapperCompilers = [],
        array $innerMappers = [],
    ): Mapper
    {
        $testCaseReflection = new ReflectionClass($this);

        $mapperShortClassName = ucfirst($name) . 'Mapper';
        $mapperNamespace = $testCaseReflection->getNamespaceName() . '\\Data';
        $mapperClassName = "{$mapperNamespace}\\{$mapperShortClassName}";

        $mapperDir = strtr(str_replace('ShipMonkTests\InputMapper', __DIR__ . '/../..', $mapperNamespace), '\\', '/');
        $mapperPath = "{$mapperDir}/{$mapperShortClassName}.php";

        $builder = new PhpCodeBuilder();
        $printer = new PhpCodePrinter();
        $mapperCode = $printer->prettyPrintFile($builder->mapperFile($mapperClassName, $mapperCompiler));
        self::assertSnapshot($mapperPath, $mapperCode);

        if (!class_exists($mapperClassName, autoload: false)) {
            require $mapperPath;
        }

        $mapperProvider = $this->createMock(MapperProvider::class);

        $mapperProvider->expects(self::any())->method('get')->willReturnCallback(
            function (string $inputClassName, array $innerMappers = []) use ($name, $providedMapperCompilers): Mapper {
                /** @var list<Mapper<mixed>> $innerMappers */
                return $this->compileMapper($name . '__' . $this->toShortClassName($inputClassName), $providedMapperCompilers[$inputClassName], [], $innerMappers);
            },
        );

        $mapper = new $mapperClassName($mapperProvider, $innerMappers);
        assert($mapper instanceof Mapper);

        return $mapper;
    }

    private function toShortClassName(string $className): string
    {
        $pos = strrpos($className, '\\');
        return $pos === false ? $className : substr($className, $pos + 1);
    }

}
