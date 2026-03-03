<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Attribute;

use Attribute;
use ShipMonk\InputMapper\Compiler\Mapper\Input\ValidatedInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Validator\ValidatorCompiler;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class ValidatedMapperCompilerProvider implements MapperCompilerProvider
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

    public function getOutputMapperCompiler(): MapperCompiler
    {
        return $this->mapperCompilerProvider->getOutputMapperCompiler();
    }

}
