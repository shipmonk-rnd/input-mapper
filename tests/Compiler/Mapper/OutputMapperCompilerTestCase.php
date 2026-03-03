<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper;

use ReflectionClass;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Compiler\Php\PhpCodePrinter;
use ShipMonk\InputMapper\Runtime\OutputMapper;
use ShipMonk\InputMapper\Runtime\OutputMapperProvider;
use ShipMonk\InputMapperTests\InputMapperTestCase;
use function assert;
use function class_exists;
use function is_a;
use function str_replace;
use function strrpos;
use function strtr;
use function substr;
use function ucfirst;

abstract class OutputMapperCompilerTestCase extends InputMapperTestCase
{

    /**
     * @param array<class-string, MapperCompiler> $providedMapperCompilers
     * @param list<OutputMapper<mixed>> $innerMappers
     * @return OutputMapper<mixed>
     */
    protected function compileOutputMapper(
        string $name,
        MapperCompiler $mapperCompiler,
        array $providedMapperCompilers = [],
        array $innerMappers = [],
    ): OutputMapper
    {
        $testCaseReflection = new ReflectionClass($this);

        $mapperShortClassName = ucfirst($name) . 'OutputMapper';
        $mapperNamespace = $testCaseReflection->getNamespaceName() . '\\Data';
        $mapperClassName = "{$mapperNamespace}\\{$mapperShortClassName}";

        $mapperDir = strtr(str_replace('ShipMonk\InputMapperTests', __DIR__ . '/../..', $mapperNamespace), '\\', '/');
        $mapperPath = "{$mapperDir}/{$mapperShortClassName}.php";

        $builder = new PhpCodeBuilder();
        $printer = new PhpCodePrinter();
        $mapperCode = $printer->prettyPrintFile($builder->outputMapperFile($mapperClassName, $mapperCompiler));
        self::assertSnapshot($mapperPath, $mapperCode);

        if (!class_exists($mapperClassName, autoload: false)) {
            require $mapperPath;
        }

        $mapperProvider = $this->createMock(OutputMapperProvider::class);

        $mapperProvider->expects(self::any())->method('get')->willReturnCallback(
            function (string $inputClassName, array $innerMappers = []) use ($name, $providedMapperCompilers): OutputMapper {
                /** @var list<OutputMapper<mixed>> $innerMappers */
                return $this->compileOutputMapper($name . '__' . $this->toShortClassName($inputClassName), $providedMapperCompilers[$inputClassName], [], $innerMappers);
            },
        );

        assert(is_a($mapperClassName, OutputMapper::class, true));
        return new $mapperClassName($mapperProvider, $innerMappers);
    }

    private function toShortClassName(string $className): string
    {
        $pos = strrpos($className, '\\');
        return $pos === false ? $className : substr($className, $pos + 1);
    }

}
