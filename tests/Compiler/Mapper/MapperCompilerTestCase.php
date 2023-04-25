<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper;

use ReflectionClass;
use ShipMonk\InputMapper\Compiler\Generator;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use ShipMonkTests\InputMapper\InputMapperTestCase;
use function assert;
use function class_exists;
use function str_replace;
use function strtr;
use function ucfirst;

abstract class MapperCompilerTestCase extends InputMapperTestCase
{

    /**
     * @param  array<class-string, Mapper<mixed>> $mappers
     * @return Mapper<mixed>
     */
    protected function compileMapper(
        string $name,
        MapperCompiler $mapperCompiler,
        array $mappers = [],
    ): Mapper
    {
        $testCaseReflection = new ReflectionClass($this);

        $mapperShortClassName = ucfirst($name) . 'Mapper';
        $mapperNamespace = $testCaseReflection->getNamespaceName() . '\\Data';
        $mapperClassName = "{$mapperNamespace}\\{$mapperShortClassName}";

        $mapperDir = strtr(str_replace('ShipMonkTests\InputMapper', __DIR__ . '/../..', $mapperNamespace), '\\', '/');
        $mapperPath = "{$mapperDir}/{$mapperShortClassName}.php";

        if (!class_exists($mapperClassName, autoload: false)) {
            $generator = new Generator();
            self::assertSnapshot($mapperPath, $generator->generateMapperFile($mapperClassName, $mapperCompiler));
            require $mapperPath;
        }

        $mapperProvider = $this->createMock(MapperProvider::class);

        foreach ($mappers as $inputClassName => $mapper) {
            $mapperProvider->expects(self::any())->method('get')->with($inputClassName)->willReturn($mapper);
        }

        $mapper = new $mapperClassName($mapperProvider);
        assert($mapper instanceof Mapper);

        return $mapper;
    }

}
