<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Attribute;

use Attribute;
use ShipMonk\InputMapper\Compiler\Mapper\CompositeMapperCompilerProvider;
use ShipMonk\InputMapper\Compiler\Mapper\Input\ValidatedInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompilerProvider;
use ShipMonk\InputMapper\Compiler\Validator\ValidatorCompiler;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class MapValidated implements CompositeMapperCompilerProvider, MapperCompilerProvider
{

    /**
     * @param list<ValidatorCompiler> $validatorCompilers
     */
    public function __construct(
        public readonly MapperCompilerProvider $mapperCompilerProvider,
        public readonly array $validatorCompilers,
    )
    {
    }

    public function getInputMapperCompiler(): MapperCompiler
    {
        return new ValidatedInputMapperCompiler($this->mapperCompilerProvider->getInputMapperCompiler(), $this->validatorCompilers);
    }

    /**
     * Validators are input-only, output mapping passes through without validation
     */
    public function getOutputMapperCompiler(): MapperCompiler
    {
        return $this->mapperCompilerProvider->getOutputMapperCompiler();
    }

    /**
     * @return list<MapperCompilerProvider>
     */
    public function getInnerMapperCompilerProviders(): array
    {
        return [$this->mapperCompilerProvider];
    }

}
